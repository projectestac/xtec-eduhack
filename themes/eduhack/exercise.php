<?php
/*
 * Template Name: Exercise
 * Template Post Type: post, page
 */

get_header();

?>

<div class="content thin eduhack-exercise">

  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Exercise header -->
    
    <div class="exercise-header">
      <div class="exercise-categories">
        <?php xteh_cateogry_image() ?>
        <?php the_category(' / ') ?>
      </div>
      <div class="exercise-pagination">
        <?php ehth_category_links(); ?>
        <?php the_posts_pagination(); ?> 
      </div>
    </div>
    
    <div id="post-<?php the_ID(); ?>" <?php post_class('single'); ?>>

      <?php $post_format = get_post_format(); ?>
      
      <?php if ( $post_format == 'video' ) : ?>
      
        <?php if ($pos=strpos($post->post_content, '<!--more-->')): ?>
    
          <div class="featured-media">
          
            <?php
                
              // Fetch post content
              $content = get_post_field( 'post_content', get_the_ID() );
              
              // Get content parts
              $content_parts = get_extended( $content );
              
              // oEmbed part before <!--more--> tag
              $embed_code = wp_oembed_get($content_parts['main']); 
              
              echo $embed_code;
            
            ?>
          
          </div> <!-- /featured-media -->
        
        <?php endif; ?>
        
      <?php elseif ( $post_format == 'gallery' ) : ?>
      
        <div class="featured-media">  
  
          <?php fukasawa_flexslider('post-image'); ?>
          
          <div class="clear"></div>
          
        </div> <!-- /featured-media -->
              
      <?php elseif ( has_post_thumbnail() ) : ?>
          
        <div class="featured-media">
    
          <?php the_post_thumbnail('post-image'); ?>
          
        </div> <!-- /featured-media -->
          
      <?php endif; ?>
      
      <div class="post-inner">
        
        <div class="post-header">
                          
          <h1 class="post-title"><?php the_title(); ?></h1>
                              
        </div> <!-- /post-header -->
            
          <div class="post-content">
          
            <?php 
            if ($post_format == 'video') { 
              $content = $content_parts['extended'];
              $content = apply_filters('the_content', $content);
              echo $content;
            } else {
              the_content();
            }
          ?>
          
          </div> <!-- /post-content -->
          
          <div class="clear"></div>
        
        <div class="post-meta-bottom">
        
          <?php 
              $args = array(
              'before'           => '<div class="clear"></div><p class="page-links"><span class="title">' . __( 'Pages:','fukasawa' ) . '</span>',
              'after'            => '</p>',
              'link_before'      => '<span>',
              'link_after'       => '</span>',
              'separator'        => '',
              'pagelink'         => '%',
              'echo'             => 1
            );
            
              wp_link_pages($args); 
          ?>
          
          <div class="clear"></div>
          
        </div> <!-- /post-meta-bottom -->
      
      </div> <!-- /post-inner -->
      
      <!-- Post navigation -->
      
      <?php
        $prev_post = get_previous_post(true);
        $next_post = get_next_post(true);
      ?>
      
      <div class="post-navigation">
        <?php if (!empty( $prev_post )): ?>
          <a class="post-nav-prev" href="<?php echo get_permalink( $prev_post->ID ); ?>">
            <p>&larr; <?php _e('Previous step', 'xtec-eduhack'); ?></p>
            <p><?= get_the_title($prev_post) ?></p>
          </a>
        <?php endif; ?>
        
        <?php if (!empty( $next_post )): ?>
          <a class="post-nav-next" href="<?php echo get_permalink( $next_post->ID ); ?>">          
            <p><?php _e('Next step', 'xtec-eduhack'); ?> &rarr;</p>
            <p><?= get_the_title($next_post) ?></p>
          </a>
        <?php endif; ?>
      </div>

    </div> <!-- /post -->

     <?php endwhile; else: ?>

    <p><?php _e("We couldn't find any posts that matched your query. Please try again.", "fukasawa"); ?></p>
  
  <?php endif; ?>    

</div> <!-- /content -->
    
<?php get_footer(); ?>