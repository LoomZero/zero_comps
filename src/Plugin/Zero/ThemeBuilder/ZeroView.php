<?php

namespace Drupal\zero_comps\Plugin\Zero\ThemeBuilder;

use Drupal\zero_entitywrapper\Base\ContentWrapperInterface;
use Drupal\zero_entitywrapper\Base\ViewWrapperInterface;
use Drupal\zero_entitywrapper\View\ViewWrapper;
use Drupal\zero_preprocess\Annotation\ZeroThemeBuilder;
use Drupal\zero_preprocess\Base\ZeroThemeBuilderBase;
use Exception;

/**
 * @ZeroThemeBuilder(
 *   id = "zero_view",
 *   theme = {
 *     "variables" = {
 *       "mode" = NULL,
 *       "view_id" = NULL,
 *       "view_display" = NULL,
 *       "entity_type" = NULL,
 *       "entity_id" = NULL,
 *       "entity_field" = NULL,
 *       "view_mode" = "teaser",
 *       "start_page" = 0,
 *       "filters" = {},
 *       "design" = NULL,
 *       "options" = {},
 *     },
 *   },
 *   validate = {
 *     "required" = {
 *       "mode" = "Please set the mode with setView() or setEntity()",
 *     },
 *   },
 * )
 */
class ZeroView extends ZeroThemeBuilderBase {

  public function preprocess(&$vars) {
    $settings = array_merge([
      'startPage' => $vars['start_page'],
      'no_more' => TRUE,
      'design' => $vars['design'] ?? NULL,
      'request' => [
        '_id' => 'zero_view',
        '_format' => 'ajax',
        'mode' => $vars['mode'],
        'view_mode' => $vars['view_mode'],
      ],
    ], $this->getOptions());

    if ($vars['mode'] === 'view') {
      $settings['request']['view_id'] = $vars['view_id'];
      $settings['request']['view_display'] = $vars['view_display'];
      $view = new ViewWrapper($vars['view_id'], $vars['view_display']);
      $view->setFullPager(NULL, $vars['start_page']);
      $vars['items'] = $view->getContentResultsCollection()->render($vars['view_mode']);
      $vars['no_more'] = $view->getResultMeta()['remain'] <= 0;
    } else if ($vars['mode'] === 'entity') {
      $settings['request']['entity_type'] = $vars['entity_type'];
      $settings['request']['entity_id'] = $vars['entity_id'];
      $settings['request']['entity_field'] = $vars['entity_field'];
      throw new Exception('The entity function is not yet supported please contact LOOM Paul to implement support for entity method.');
    }

    if (!empty($vars['filters']) && !empty($vars['design'])) {
      foreach ($vars['filters'] as $key => $filter) {
        $vars['filters'][$key]['#design'] = $vars['design'];
      }
    }

    $this->addSettings($settings);
    $vars['uuid'] = $this->getUUID();
  }

  public function setView(string $view_id, string $view_display = 'master', string $view_mode = 'teaser', int $start_page = 0): self {
    $this->theme[$this->getKey('mode')] = 'view';
    $this->theme[$this->getKey('view_id')] = $view_id;
    $this->theme[$this->getKey('view_display')] = $view_display;
    $this->theme[$this->getKey('view_mode')] = $view_mode;
    $this->theme[$this->getKey('start_page')] = $start_page;
    return $this;
  }

  public function setViewWrapper(ViewWrapperInterface $wrapper, string $view_mode = 'teaser'): self {
    return $this->setView($wrapper->id(), $wrapper->getDisplay(), $view_mode, $wrapper->getCurrentPage());
  }

  public function setEntity(string $entity_type, $entity_id, string $field, string $view_mode = 'teaser'): self {
    $this->theme[$this->getKey('mode')] = 'entity';
    $this->theme[$this->getKey('entity_type')] = $entity_type;
    $this->theme[$this->getKey('entity_id')] = $entity_id;
    $this->theme[$this->getKey('entity_field')] = $field;
    $this->theme[$this->getKey('view_mode')] = $view_mode;
    return $this;
  }

  public function setEntityWrapper(ContentWrapperInterface $wrapper, string $field, string $view_mode = 'teaser'): self {
    return $this->setEntity($wrapper->type(), $wrapper->id(), $field, $view_mode);
  }

  public function addFilter(string $key, array $formElement, string $trigger = NULL): self {
    if ($trigger === NULL) {
      if (empty($formElement['#type']) || $formElement['#type'] !== 'select') {
        $trigger = 'delay:500';
      } else {
        $trigger = 'change';
      }
    }

    $formElement['#attributes']['data-filter-key'] = $key;
    $formElement['#attributes']['data-filter-trigger'] = $trigger;
    $this->theme[$this->getKey('filters')][$key] = $formElement;
    return $this;
  }

  public function addFilterSelect(string $key, array $options, array $formElement = [], string $trigger = NULL): self {
    $formElement['#type'] = 'select';
    $formElement['#options'] = $options;
    return $this->addFilter($key, $formElement, $trigger);
  }

  public function design(string $design) {
    $this->theme[$this->getKey('design')] = $design;
    return $this;
  }

  public function getOptions(): array {
    return $this->theme[$this->getKey('options')] ?? [];
  }

}
