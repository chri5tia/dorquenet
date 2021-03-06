<?php

namespace Drupal\term_merge_manager\EventSubscriber;

use Drupal\term_merge_manager\Entity\TermMergeFrom;
use Drupal\term_merge_manager\Entity\TermMergeInto;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class DefaultSubscriber.
 */
class DefaultSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['term_merge.terms_merged'] = ['term_merge_merge_action'];

    return $events;
  }

  /**
   * This method is called whenever the term_merge.merge_action event is
   * dispatched.
   *
   * @param \Drupal\term_merge\TermsMergedEvent $event
   */
  public function term_merge_merge_action(Event $event) {
    
    $into = $event->getTargetTerm();
    $from = $event->getSourceTerms();

    // Load existing rule or create it.
    $mergeintoid = TermMergeInto::loadIdByTid($into->id());

    if ($mergeintoid === FALSE) {
      $terminto = TermMergeInto::create([
        'tid' => $into->id(),
        'vid' => $into->bundle(),
      ]);
      $terminto->save();
      $mergeintoid = $terminto->id();
    }

    // Create or update items.
    /** @var \Drupal\taxonomy\TermInterface $item */
    foreach ($from as $index => $item) {
      $vid = $item->bundle();
      $name = $item->getName();

      $from = TermMergeFrom::loadByVidName($vid, $name);
      if ($from === FALSE) {
        $from = TermMergeFrom::create();
      }

      $from->set('tmiid', $mergeintoid);
      $from->set('vid', $vid);
      $from->set('name', $name);
      $from->save();
    }

  }

}
