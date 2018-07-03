define(['jquery', 'core/config', 'core/str','block_my_calendar/bootstrap.datepicker'],
    function ($, config,lang) {
        return {
            init: function (parametros) {
                // Template
                $.fn.datepicker.defaults["templates"] = {
                    leftArrow: '<span class="pull-right fa fa-chevron-left"></span>',
                    rightArrow: '<span class="pull-left fa fa-chevron-right"></span>'
                };
                // Pacote de tradução
                switch (parametros.lang) {
                    case 'es':
                        $.fn.datepicker.dates["pt-BR"] =
                            {
                                days: ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"],
                                daysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
                                daysMin: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
                                months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                                monthsShort: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],
                                today: "Hoy",
                                monthsTitle: "Meses",
                                clear: "Borrar",
                                weekStart: 1,
                                format: "dd/mm/yyyy"
                            };
                        break;
                    case 'pt_br':
                        $.fn.datepicker.dates["pt-BR"] =
                            {
                                days: ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"],
                                daysShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
                                daysMin: ["D", "S", "T", "Q", "Q", "S", "S"],
                                months: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
                                monthsShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
                                today: "Hoje",
                                monthsTitle: "Meses",
                                clear: "Limpar",
                                format: "dd/mm/yyyy"
                            };
                        break;
                }




                //Url ajax api
                var URL = config.wwwroot + '/blocks/my_calendar/calendar.ajax.php?sesskey=' + config.sesskey;
                buscarEventosDoMes(new Date());
                renderizeEvents({date: new Date()});
                //

                /**
                 * Renderiza os eventos do mês
                 * @param date
                 * @returns {*}
                 */
                function updateCalendar(date) {
                    for (var x in calendario) {
                        if (calendario.hasOwnProperty(x)) {
                            if (date.getMonth() == calendario[x].data.getMonth()) {
                                if (date.getDate() == calendario[x].data.getDate()) {

                                    return {

                                        tooltip: calendario[x].titulo,
                                        classes: 'today'
                                    };
                                }
                            }
                        }
                    }
                }

                /**
                 * Renderiza os eventos do dia
                 * @param date
                 */
                function renderizeEvents(date) {
                    var conteudo = '';
                    parametros.dt1 = parseInt(date.date.getTime() / 1000);
                    parametros.dt2 = parseInt(date.date.getTime() / 1000);
                    $.post(URL, parametros).done(function (x) {
                        for (var prop in x) {
                            if (x.hasOwnProperty(prop)) {
                                data = new Date(x[prop].timestart * 1000);
                                data.setHours(data.getHours());
                                conteudo +=
                                    "<a href='" + x[prop].url + "' class='cal-box'>"
                                    + "<div class='cal-h cal-h-a'>"
                                    + padding(data.getHours(), 2) + "h" + padding(data.getMinutes(), 2)
                                    + "</div>"
                                    + "<div class='cal-h cal-h-b'>"
                                    + x[prop].name
                                    + "</div>"
                                    + "</a>";
                                ;
                            }
                        }
                        if (conteudo == '')
                            conteudo = "<a class='center'>Nenhum evento registrado</a>";
                        $('.event-render').fadeOut('fast').fadeIn("slow").html(conteudo);
                    });
                }

                /**
                 * Busca os eventos do Mês
                 *
                 * @param date
                 */
                function buscarEventosDoMes(date) {
                    calendario = [];

                    var hoje = new Date();
                    var x1 = new Date(date.getFullYear(), date.getMonth(), 1);
                    var x2 = new Date(date.getFullYear(), date.getMonth() + 1, 0);
                    parametros.dt1 = x1.getTime() / 1000;
                    parametros.dt2 = x2.getTime() / 1000;
//
                    $.post(URL, parametros).done(function (x) {
                        for (var prop in x) {
                            if (x.hasOwnProperty(prop)) {
                                data = new Date(x[prop].timestart * 1000);
                                calendario.push({data: data, titulo: x[prop].name});
                            }
                        }
                        $('#block_my_calendar').datepicker('update', new Date(date.getFullYear(), date.getMonth(), hoje.getDate()));
                    });
                }


                /**
                 * Formata com zeros a esquerda
                 * @param value
                 * @param length
                 * @returns {*}
                 */
                function padding(value, length) {
                    var paddingCount = length - String(value).length;
                    for (var i = 0; i < paddingCount; i++) {
                        value = '0' + value;
                    }
                    return value;
                }

                $('#block_my_calendar').datepicker({
                    language: "pt-BR",
                    // todayHighlight: true,
                    beforeShowDay: updateCalendar
                }).on('changeDate', function (e) {
                    renderizeEvents(e)
                }).on('changeMonth', function (e) {
                    buscarEventosDoMes(e.date);
                });
                $('.datepicker.datepicker-inline').addClass('center-block');
            }
        };
    });
