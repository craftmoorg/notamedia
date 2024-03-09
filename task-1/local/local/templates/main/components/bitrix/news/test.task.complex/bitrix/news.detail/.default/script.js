$(document).ready(function () {
    $('#toggle-hide').click(function () {
        let description = $('.description');

        if (description.hasClass('hide')) {
            description.removeClass('hide');
            $(this).html('Скрыть описание');
        } else {
            description.addClass('hide');
            $(this).html('Раскрыть описание');
        }
    });
});