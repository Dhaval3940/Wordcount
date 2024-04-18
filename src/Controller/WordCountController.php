<?php

namespace Drupal\wordcount\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An WordCount controller return table with number of words added by each user.
 */
class WordCountController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {

    $database = \Drupal::database();

    $posts = $database->query("SELECT nbd.body_value AS body, nbd.body_summary AS summary, nfd.title AS title, nhr.field_hr_content_value AS hr_content, nfd.uid, u.name
                             FROM {node__body} AS nbd, {node_field_data} AS nfd, {node__field_hr_content} AS nhr, {users_field_data} AS u
                             WHERE nfd.nid=nbd.entity_id AND u.uid=nfd.uid AND nbd.langcode='en'");

    $rows = [];
    foreach ($posts as $post) {
      $id = $post->uid;
      if (!isset($rows[$id])) {
        $rows[$id]['id'] = $id;
        $rows[$id]['name'] = ($id) ? $post->name : 'Anonymous';
        $rows[$id]['posts'] = 0;
        $rows[$id]['num_words_post'] = 0;
        $rows[$id]['avg_post'] = 0;
        $rows[$id]['comments'] = 0;
        $rows[$id]['num_words_comments'] = 0;
        $rows[$id]['avg_comment'] = 0;
        $rows[$id]['total'] = 0;
      }
      $rows[$id]['posts']++;
      $rows[$id]['num_words_post'] += str_word_count(strip_tags($post->body));
      $rows[$id]['num_words_post'] += str_word_count(strip_tags($post->summary));
      $rows[$id]['num_words_post'] += str_word_count(strip_tags($post->title));
      $rows[$id]['num_words_post'] += str_word_count(strip_tags($post->hr_content));
    }

    $comments = [];
    $comments = $database->query("SELECT ccb.comment_body_value AS comment, cfd.uid, u.name
                                FROM {comment__comment_body} AS ccb, {comment_field_data} as cfd, {users_field_data} as u
                                WHERE cfd.cid=ccb.entity_id and u.uid=cfd.uid");
    foreach ($comments as $comment) {
      $id = $comment->uid;
      if (!isset($rows[$id])) {
        $rows[$id]['id'] = $id;
        $rows[$id]['name'] = ($id) ? $comment->name : 'Anonymous';
        $rows[$id]['posts'] = 0;
        $rows[$id]['num_words_post'] = 0;
        $rows[$id]['avg_post'] = 0;
        $rows[$id]['comments'] = 0;
        $rows[$id]['num_words_comments'] = 0;
        $rows[$id]['avg_comment'] = 0;
        $rows[$id]['total'] = 0;
      }
      $rows[$id]['comments']++;
      $rows[$id]['num_words_comments'] += str_word_count(strip_tags($comment->comment));
    }

    foreach ($rows as $k => $v) {
      $rows[$k]['avg_post'] = ($rows[$k]['posts']) ? number_format($rows[$k]['num_words_post'] / $rows[$k]['posts'], 1) : 0;
      $rows[$k]['avg_comment'] = ($rows[$k]['comments']) ? number_format($rows[$k]['num_words_comments'] / $rows[$k]['comments'], 1) : 0;
      $rows[$k]['total'] = $rows[$k]['num_words_post'] + $rows[$k]['num_words_comments'];
    }

    $header = [
      'id' => $this->t('ID'),
      'name' => $this->t('Name'),
      'posts' => $this->t('Nodes'),
      'num_word_posts' => $this->t('Words in Nodes'),
      'avg_post' => $this->t('Words per Node'),
      'comments' => $this->t('Comments'),
      'num_words_comments' => $this->t('Words in Comments'),
      'avg_comment' => $this->t('Words per Comment'),
      'total' => $this->t('Total words'),
    ];

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No nodes or comments found!'),
    ];

    return $table;

  }

}
