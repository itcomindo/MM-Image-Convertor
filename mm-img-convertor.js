// jQuery(function () {
//     jQuery('.convert_to_webp.column-convert_to_webp button').click(function () {
//         alert('clicked');
//     });
// });



jQuery(document).ready(function ($) {
    $('body').on('click', '.convert-to-webp-btn', function () {
        var attachmentId = $(this).data('attachment-id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mm_convert_to_webp',
                attachment_id: attachmentId
            },
            success: function (response) {
                alert('Konversi ke WebP berhasil!');
            },
            error: function () {
                alert('Terjadi kesalahan dalam konversi.');
            }
        });
    });
});
