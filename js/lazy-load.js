$(function() {
    $('.lazy').Lazy({
        scrollDirection: 'vertical',
        effect: 'fadeIn',
        visibleOnly: true,
        data_attribute  : "data-src",
        onError: function(element) {
            console.log('error loading ' + element.data('data-src'));
        }
    });
});