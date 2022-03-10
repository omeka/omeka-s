(function($) {
    $(document).ready(function() {
        var galleryState = ($('#itemfiles li').length > 1) ? true : false;

        var lgContainer = document.getElementById('itemfiles');
        var inlineGallery = lightGallery(lgContainer, {
            container: lgContainer,
            dynamic: false,
            hash: false,
            closable: false,
            thumbnail: true,
            selector: '.media.resource',
            showMaximizeIcon: true,
            autoplayFirstVideo: false,
            plugins: [
                lgThumbnail,lgZoom,lgVideo,
            ],
        });

        inlineGallery.openGallery();
    });
})(jQuery)
