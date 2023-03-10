(function($) {
    $(document).ready(function() {
        var lgContainer = document.getElementById('itemfiles');
        var inlineGallery = lightGallery(lgContainer, {
            licenseKey: '999D4292-0B8E4F74-9CC803A5-D4AA79D6',
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
