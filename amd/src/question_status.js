define(['jquery',
    'core/ajax',
    'core/templates',
    'core/notification'],
        function ($,
                ajax,
                templates,
                notification) {
            return /** @alias module:quiz_participation/quiz */ {
                /**
                 * Load the user programs!
                 *
                 * @method programs
                 */
                questions: function () {
                    // Add a click handler to the button.
                    $(document).on('change', '#course-list-quiz', function (e) {
                        e.preventDefault();
                        var cid = $("#course-list-quiz :selected").val();
                        var WAITICON = {'pix': M.util.image_url("i/loading_small", 'core'), 'component': 'moodle'};
                        var loader = $('<img />')
                                .attr('src', M.util.image_url(WAITICON.pix, WAITICON.component))
                                .addClass('spinner');
                        $('.course-quiz-wrongquestion').html('<div class="text-center">' + loader.get(0).outerHTML + '</div>');
                        var promises = ajax.call([{
                                methodname: 'block_question_status_get_quiz_wrongquestions',
                                args: {
                                    courseid: cid,
                                }
                            }]);
                        promises[0].done(function (data) {
                            $('.course-quiz-wrongquestion').html(data.displayhtml);
                        }).fail(notification.exception);
                    });
                }

            };
        });
