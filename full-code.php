=========================================HTML Form ======================================

<form action="" id="itemMoveFrm" enctype="multipart/form-data" method="post">
<input type="file" id="files" name="imagefile[]" multiple="multiple" class="files-data" accept="image/*" />
<input type="hidden" id="postId" value="<?php echo get_the_ID(); ?>" />
 <input type="submit" class="buttonclass" value="Save" id="submit-btn-id"/>
</form>

//============================== script code // code =====================================

jQuery(document).ready(function(){
  jQuery('#submit-btn-id').click(function(){
       var fd = new FormData();
        var files_data = jQuery('.files-data'); // The <input type="file" /> field
       jQuery.each(jQuery(files_data), function (i, obj) {
       jQuery.each(obj.files, function (j, file) {
       fd.append('files[' + j + ']', file);
       });
       });
       fd.append('action', 'hp_upload_file');
       fd.append('post_id', jQuery('#postId').val());
        jQuery.ajax({
          type: 'POST',
          url: '<?php echo admin_url('admin-ajax.php'); ?>',
          data: fd,
          async: false,
          contentType: false,
          processData: false,
          success: function (response) {
          jQuery('.upload-response').html(response); // Append Server Response
         }
       });
  });
});


//============================== Functions.php // code =====================================

function hp_upload_file() {
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;  // The parent ID of our attachments
    $valid_formats = array("jpg", "png", "gif", "bmp", "jpeg"); // Supported file types
    $max_file_size = 1024 * 500; // in kb
    $max_image_upload = 10; // Define how many images can be uploaded to the current post
    $wp_upload_dir = wp_upload_dir();
    $path = $wp_upload_dir['path'] . '/';
    $count = 0;

    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_parent' => $post_id,
        'exclude' => get_post_thumbnail_id() // Exclude post thumbnail to the attachment count
    ));

    // Image upload handler
    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        // Check if user is trying to upload more than the allowed number of images for the current post
        if (( count($attachments) + count($_FILES['files']['name']) ) > $max_image_upload) {
            $upload_message[] = "Sorry you can only upload " . $max_image_upload . " images for each Ad";
        } else {
            foreach ($_FILES['files']['name'] as $f => $name) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                // Generate a randon code for each file name

                if ($_FILES['files']['error'][$f] == 4) {
                    continue;
                }

                if ($_FILES['files']['error'][$f] == 0) {
                    if (!in_array(strtolower($extension), $valid_formats)) {
                        $upload_message[] = "$name is not a valid format";
                        continue;
                    } else {
                        $response = array();
                        if ($_FILES['files']['name'][$f]) {
                            $file = array(
                                'name' => $_FILES['files']['name'][$f],
                                'type' => $_FILES['files']['type'][$f],
                                'tmp_name' => $_FILES['files']['tmp_name'][$f],
                                'error' => $_FILES['files']['error'][$f],
                                'size' => $_FILES['files']['size'][$f]
                            );
//                            $file_info = wp_handle_upload($file, $upload_overrides);
                            require_once( ABSPATH . 'wp-admin/includes/admin.php' );
                            if (!function_exists('wp_handle_upload')) {
                                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                            }
                            $file_return = wp_handle_upload($file, array('test_form' => false));

                            if (isset($file_return['error']) || isset($file_return['upload_error_handler'])) {
                                return false;
                            } else {
                                $filename = $file_return['file'];
                                $attachment = array(
                                    'post_mime_type' => $file_return['type'],
                                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                                    'post_content' => '',
                                    'post_status' => 'inherit',
                                    'guid' => $file_return['url'],
                                    'post_parent' => $post_id
                                );
                                add_post_meta($post_id, 'wpcf-item-images', $file_return['url']);
                                $attachment_id = wp_insert_attachment($attachment, $file_return['url']);
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                $attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
                                wp_update_attachment_metadata($attachment_id, $attachment_data);
                                set_post_thumbnail($post_id, $attachment_id);
                                if (0 < intval($attachment_id)) {
                                    $response['message'] = 'Done!' . $attachment_id;
                                }
                                $count++;
                            }
                        }
                    }
                }
            }
        }
    }
    // Loop through each error then output it to the screen
    if (isset($upload_message)) :
        foreach ($upload_message as $msg) {
            printf(__('<p class="bg-danger">%s</p>', 'wp-trade'), $msg);
        }
    endif;

    // If no error, show success message
    if ($count != 0) {
        printf(__('<p class = "bg-success">%d files added successfully!</p>', 'wp-trade'), $count);
    }

    exit();
}

add_action('wp_ajax_hp_upload_file', 'hp_upload_file');
add_action('wp_ajax_nopriv_hp_upload_file', 'hp_upload_file');

