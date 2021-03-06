<article class="blog-view">
    
<?php
$photo = $blogPost->photo;
if($photo && $settings['blog']['display_image_on_post_view']) : ?>
    <figure class="blog-view-figure">
    <?php 
        echo $this->Html->image('/uploads/blogposts/photo/' . 
            $blogPost->photo_dir . 
            '/view_' . $photo, [
                'class' => 'blog-view-img',
                'alt' => $blogPost->photo_alt
            ]
        ); 
    ?>
    </figure>
 <?php endif ?>
       
    <header class="blog-view-header">
    <h1 class="blog-view-title"><?= h($blogPost->title) ?></h1>
    <time class="blog-view-time" datetime="<?= $this->Time->format($blogPost->created, 'YYYY-MM-dd HH:mm:ssZ'); ?>">
        <?= $this->Time->format($blogPost->created,'d MMM YYYY'); ?>
    </time>
    </header>

    <div class="blog-view-body">
    <?= $blogPost->body ?>
    </div>

</article>

<?php
$this->assign('title', $blogPost->title . ' | ' . $settings['Site']['title']);
$this->Html->meta(
    'description',
    $blogPost->meta_description,
    ['block' => true]
);
if($settings['blog']['display_breadcrumbs_on_post_view']) {
    $breadcrumbs = [
        ['path' => $this->Url->build(['controller' => 'blogPosts', 'action' => 'index']), 'title' => $settings['blog']['title']],
        ['path' => $this->Url->build(['controller' => 'blogPosts', 'action' => 'view', $blogPost->slug]), 'title' => $blogPost->title]
    ];
    $this->set('breadcrumbs', $breadcrumbs);
    $this->set('count_breadcrumbs', count($breadcrumbs));
}
?>

<?php if ($settings['blog']['display_archives_view_cell']) : ?>
<?php
$this->start($settings['blog']['archive_cell_region']);
echo $this->cell('CakephpSpongeBlog.Archive', [], ['cache' => true]);
$this->end();
?>
<?php endif; ?>
