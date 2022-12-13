(function($) {
    $(document).ready(function() {
        var lgContainer = document.getElementById('itemfiles');
        var inlineGallery = lightGallery(lgContainer, {
            container: lgContainer,
            dynamic: false,
            hash: true,
            closable: false,
            thumbnail: true,
            selector: '.media.resource',
            showMaximizeIcon: true,
            autoplayFirstVideo: false,
            exThumbImage: 'data-thumb',
            flipVertical: false,
            flipHorizontal: false,
            plugins: [
                lgThumbnail,lgZoom,lgVideo,lgHash,lgRotate
            ],
        });

        inlineGallery.openGallery();
    });
})(jQuery)
