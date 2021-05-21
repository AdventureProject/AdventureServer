<<<<<<< HEAD
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
=======
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
>>>>>>> 0bc6615a0373328a595c25b5a586a0a01329692a
});