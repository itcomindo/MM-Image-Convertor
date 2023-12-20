<?php

/**
 * Plugin Name: MM Image Convertor
 * Plugin URI: https://budiharyono.id
 * Description: Convert Image from JPG or PNG to WebP (untuk menghkonversi gambar JPG atau PNG menjadi WebP) Saat di upload maupun di Media Library.
 * Version: 1.0.0
 * Author: Budi Haryono
 * Author URI: https://budiharyono.id
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
defined('ABSPATH') || exit;
/**
 * Convertor JPG or PNG to WebP
 * @param array $uploadedfile Array of data for a single file.
 */
function mm_jpg_png_to_webp_converter($uploadedfile)
{
    $file_path = $uploadedfile['file'];
    $file_info = pathinfo($file_path);
    $extension = strtolower($file_info['extension']);
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $image = @imagecreatefrompng($file_path);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
        }
        if ($image) {
            imagewebp($image, $webp_path, 100);
            imagedestroy($image);
            unlink($file_path);
            $uploadedfile['file'] = $webp_path;
            $uploadedfile['type'] = 'image/webp';
        }
    }
    return $uploadedfile;
}
add_filter('wp_handle_upload', 'mm_jpg_png_to_webp_converter');
/**
 * Function to add a new column to the Media Library list table.
 */
function mm_add_webp_convert_column($columns)
{
    $columns['convert_to_webp'] = 'Convert to WebP';
    return $columns;
}
add_filter('manage_media_columns', 'mm_add_webp_convert_column');
/**
 * display button
 * @param string $column_name Name of the custom column.
 */
function mm_display_webp_convert_button($column_name, $id)
{
    if ($column_name === 'convert_to_webp') {
        $attachment = get_attached_file($id);
        $file_type = wp_check_filetype($attachment);
        if (in_array($file_type['ext'], ['jpg', 'png'])) {
            echo '<button class="convert-to-webp-btn" data-attachment-id="' . esc_attr($id) . '">Convert to WebP</button>';
        }
    }
}
add_action('manage_media_custom_column', 'mm_display_webp_convert_button', 10, 2);
/**
 * convert to webp callback
 */
function mm_convert_to_webp_callback()
{
    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    if (!$attachment_id) {
        echo 'ID lampiran tidak valid';
        wp_die();
    }
    $attachment_path = get_attached_file($attachment_id);
    $file_info = pathinfo($attachment_path);
    $extension = strtolower($file_info['extension']);
    if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
        echo 'Format file tidak didukung untuk konversi.';
        wp_die();
    }
    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
    $success = false;
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = @imagecreatefromjpeg($attachment_path);
            break;
        case 'png':
            $image = @imagecreatefrompng($attachment_path);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
    }
    if ($image) {
        $success = imagewebp($image, $webp_path, 100);
        imagedestroy($image);
    }
    if (!$success) {
        echo 'Gagal mengonversi gambar ke WebP.';
        wp_die();
    }
    // Update attachment metadata to point to new WebP image
    $new_file = $webp_path;
    $wp_filetype = wp_check_filetype($new_file, null);
    $attachment_data = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($new_file)),
        'post_content' => '',
        'post_status' => 'inherit'
    ];
    $attach_id = wp_insert_attachment($attachment_data, $new_file, $attachment_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    echo 'Konversi ke WebP berhasil.';
    wp_die();
}
add_action('wp_ajax_mm_convert_to_webp', 'mm_convert_to_webp_callback');
/**
 * enqueue media script
 */
function mm_enqueue_media_script()
{
    wp_enqueue_script('mm-media-library-script', plugin_dir_url(__FILE__) . 'mm-img-convertor.js', ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'mm_enqueue_media_script');
