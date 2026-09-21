jQuery(document).ready(function () {

    if (window.mmLoginPush && mmLoginPush.notification) {
        mmPushNotification(mmLoginPush.notification);
    }

});
