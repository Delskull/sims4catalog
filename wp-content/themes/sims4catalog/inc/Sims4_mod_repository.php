<?php

class Sims4_mod_repository
{
    private int $mod_id;

    public function __construct(int $mod_id = 0)
    {
        $this -> mod_id = $mod_id ? $mod_id : get_the_ID();
    }

    public function get_all_data(): array
    {
        return [
            'download' => get_field('mod_download_url', $this ->mod_id),
            'author' => get_field('mod_author', $this -> mod_id) ?? 'Неизвестный автор',
            'source' => get_field('mod_source_url', $this -> mod_id),
            'thumbnail' => $this -> get_thumbnail(),
            'screenshots' => $this -> get_screenshots(),
        ];
    }

    private function get_thumbnail(): string
    {
        $raw_thumbnail = get_the_post_thumbnail_url($this -> mod_id, 'large');
        return $raw_thumbnail ? $raw_thumbnail : get_theme_file_uri('src/img/no-image.jpg');
    }

    private function get_screenshots(): array
    {
        $screenshots = [];

        if (has_post_thumbnail($this->mod_id)) {
            $screenshots[] = get_the_post_thumbnail_url($this->mod_id, 'large');
        }

        $attachments = get_posts([
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_parent'    => $this->mod_id,
            'post_mime_type' => 'image',
            'exclude'        => get_post_thumbnail_id($this->mod_id),
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        ]);

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $img_url = wp_get_attachment_url($attachment->ID);
                if ($img_url) {
                    $screenshots[] = $img_url;
                }
            }
        }

        return $screenshots;
    }
}