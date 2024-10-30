/* Script for Image Text Banner media upload */
jQuery(document).ready( function($) {
    function media_upload(button_class) {
        var _custom_media = true,
        _orig_send_attachment = wp.media.editor.send.attachment;

        $('body').on('click', button_class, function(e) {
            var button_id ='#'+$(this).attr('id');
            var self = $(button_id);
            var send_attachment_bkp = wp.media.editor.send.attachment;
            var button = $(button_id);
            var id = button.attr('id').replace('_button', '');
            _custom_media = true;
            wp.media.editor.send.attachment = function(props, attachment){
                if ( _custom_media  ) {
                    $('.custom_media_id').val(attachment.id);
                    $('.custom_media_url').val(attachment.sizes.large.url); // We add the large image
					$('#'+id+'-img').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                    $('#'+id+'-img .custom_media_image').attr('src',attachment.sizes.large.url).css('display','block');
                } else {
                    return _orig_send_attachment.apply( button_id, [props, attachment] );
                }
            }
            wp.media.editor.open(button);
                return false;
        });
    }
    media_upload('.custom_media_button.button');
});