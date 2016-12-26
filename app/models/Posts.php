<?php
namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Created by PhpStorm.
 * User: thanhtu
 * Date: 12/20/2016
 * Time: 3:27 PM
 */
class Posts extends Model
{
    protected $table = 'wp_posts';
    protected $prefix;
    protected $table_postmeta;
    protected $table_terms;
    protected $table_term_taxonomy;
    protected $term_relationships;
    protected $table_term_options;
    protected $table_comments;
    protected $table_commentmeta;
    protected $table_user_timeline_images;
    protected $table_1000ngayvang_timeline;
    protected $table_usermeta;
    protected $table_userinfo;

    public function __construct()
    {
        parent::__construct();
        $this->prefix = "wp_";
        $this->table_posts = $this->prefix . "posts";
        $this->table_postmeta = $this->prefix . "postmeta";
        $this->table_terms = $this->prefix . "terms";
        $this->table_term_taxonomy = $this->prefix . "term_taxonomy";
        $this->table_term_relationships = $this->prefix . "term_relationships";
        $this->table_term_options = $this->prefix . "options";
        $this->table_comments = $this->prefix . "comments";
        $this->table_commentmeta = $this->prefix . "commentmeta";
        $this->table_usermeta = $this->prefix . "usermeta";
    }

    /**
     * Get Posts
     *
     * @param $id
     * @return mixed
     */
    function getBlogNews($limit)
    {
        $data = DB::select("SELECT p.ID, 
                                   p.post_title, 
                                   p.post_type,
                                   post_excerpt,
                                   post_content,
                                   thumb.meta_value as imgsrc
                            FROM {$this->table_term_relationships} as relationship,
                                 {$this->table_terms} as terms,
                                 {$this->table_posts} as p
                            JOIN {$this->table_postmeta} thumb 
                                  ON p.ID = thumb.post_id AND thumb.meta_key = 'post_thumb_image_url'
                            WHERE p.post_status = 'publish'
                                  AND p.ID = relationship.object_id
                                  AND terms.term_id = relationship.term_taxonomy_id
                                  AND p.post_type = 'post'
                                  AND terms.slug = 'blog'
                            ORDER BY p.ID DESC
                            LIMIT ".$limit);
        if ($data) {

            // handing excert
            if (trim($data[0]->post_excerpt) == "") {
                $data[0]->post_excerpt = Str::words($data[0]->post_content, 30);
            }

            // autop content
            $data[0]->post_content = \Xmeltrut\Autop\Autop::format($data[0]->post_content);

        }
        return $data;
    }

    /**
     * Get Posts , post_type = technical_new
     *
     * @param $id
     * @return mixed
     */
    function getTechnicalNews($type, $limit)
    {
        $datas = DB::select("SELECT p.ID, 
                                   p.post_title, 
                                   p.post_type,
                                   week_start.meta_value as week_start,
                                   week_end.meta_value as week_end,
                                   in_week_news.meta_value as in_week_news
                            FROM {$this->table_posts} p
                            JOIN {$this->table_postmeta} week_start 
                                  ON p.ID = week_start.post_id AND week_start.meta_key = 'week_start'
                            JOIN {$this->table_postmeta} week_end 
                                  ON p.ID = week_end.post_id AND week_end.meta_key = 'week_end'
                            JOIN {$this->table_postmeta} in_week_news 
                                  ON p.ID = in_week_news.post_id AND in_week_news.meta_key = 'in_week_news'
                            WHERE p.post_status = 'publish'
                                  AND p.post_type = '".$type."'
                            ORDER BY p.ID DESC
                            LIMIT ".$limit);
        if ($datas) {

            foreach ($datas as $data) {
                // handling date
                $week_start = date_format(date_create($data->week_start),'d/m');
                $week_end   = date_format(date_create($data->week_end),'d/m/Y');
                $data->time = 'Tuần từ '. $week_start . ' - ' . $week_end ;

                // handling child post
                $in_week_new_ids = unserialize($data->in_week_news);
                $child_technical_news = $this->getPost($in_week_new_ids);
                $data->in_week_news   = $child_technical_news;
            }

        }
        return $datas;
    }

    /**
     * Get Posts , post_type = library_group
     *
     * @param $id
     * @return mixed
     */
    function getLibrary($type)
    {
        $datas = DB::select("SELECT p.ID, 
                                   p.post_title, 
                                   library_post.meta_value as library_post
                            FROM {$this->table_posts} p
                            JOIN {$this->table_postmeta} library_post 
                                  ON p.ID = library_post.post_id AND library_post.meta_key = 'library_post'
                            WHERE p.post_status = 'publish'
                                  AND p.post_type = '".$type."'
                            ORDER BY p.ID DESC");
        if ($datas) {

            foreach ($datas as $data) {

                // handling child post
                $library_post_ids = unserialize($data->library_post);
                $child_library_posts = $this->getLibraryPost($library_post_ids);
                $data->library_post   = $child_library_posts;
            }

        }
        return $datas;
    }

    function getLibraryPost($ids, $type = 'library_post'){
        $data = DB::select("SELECT p.ID, 
                                   p.post_excerpt, 
                                   p.post_content, 
                                   p.post_title, 
                                   icon_name.meta_value as icon_name
                            FROM {$this->table_posts} p
                            JOIN {$this->table_postmeta} icon_name 
                                  ON p.ID = icon_name.post_id AND icon_name.meta_key = 'icon_name'
                            WHERE p.ID IN (".implode(',',$ids).")
                                  AND p.post_status = 'publish'
                                  AND p.post_type = '".$type."'");
        if ($data) {
            // handing excert
            if (trim($data[0]->post_excerpt) == "") {
                $data[0]->post_excerpt = Str::words($data[0]->post_content, 30);
            }

            // autop content
            $data[0]->post_content = \Xmeltrut\Autop\Autop::format($data[0]->post_content);
        }
        return $data;

        if (!empty($result) and count($result) == 1) {
            $result = array_shift($result);
        }
        return $result;
    }

    function getPost($ids, $type = 'post'){
        $data = DB::select("SELECT p.ID, 
                                   p.post_excerpt, 
                                   p.post_content, 
                                   p.post_title, 
                                   p.post_author, 
                                   p.post_date
                            FROM {$this->table_posts} p
                            WHERE p.ID IN (".implode(',',$ids).")
                                  AND p.post_status = 'publish'
                                  AND p.post_type = '".$type."'");
        if ($data) {
            // handing excert
            if (trim($data[0]->post_excerpt) == "") {
                $data[0]->post_excerpt = Str::words($data[0]->post_content, 30);
            }

            // autop content
            $data[0]->post_content = \Xmeltrut\Autop\Autop::format($data[0]->post_content);
        }
        return $data;

        if (!empty($result) and count($result) == 1) {
            $result = array_shift($result);
        }
        return $result;
    }
}