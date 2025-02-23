jQuery(document).ready(function($) {
    $('.nb-copy-button').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        alert('Shortcode copied to clipboard: ' + shortcode);
    });
});
