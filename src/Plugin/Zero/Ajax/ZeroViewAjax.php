<?php

namespace Drupal\zero_comps\Plugin\Zero\Ajax;

use Drupal\zero_ajax_api\Annotation\ZeroAjax;
use Drupal\zero_ajax_api\ZeroAjaxBase;
use Drupal\zero_ajax_api\ZeroAjaxRequest;
use Drupal\zero_entitywrapper\View\ViewWrapper;

/**
 * @ZeroAjax(
 *   id = "zero_view",
 *   params = {
 *     "mode" = "+string",
 *     "view_id" = "string",
 *     "view_display" = "string",
 *     "view_mode" = "+string",
 *     "page" = "+int",
 *     "filters" = "array",
 *   },
 * )
 */
class ZeroViewAjax extends ZeroAjaxBase {

  public function response(ZeroAjaxRequest $request) {
    $response = [];
    $params = $request->getParams();

    if ($params['mode'] === 'view') {
      $filters = array_filter($params['filters'], function($value) {
        return strlen($value . '') !== 0; // to fix values like "0"
      });

      $view = new ViewWrapper($params['view_id'], $params['view_display']);
      $view->setExposedInput($filters);
      $view->setFullPager(null, $params['page']);
      $response['items'] = [];
      foreach ($view->getContentResults() as $result) {
        $response['items'][] = [
          'content' => $this->render($result->render($params['view_mode'])),
        ];
      }
      $request->setMeta('view', $view->getResultMeta());
      $request->setMeta('page', $view->getCurrentPage());
      $request->addInvoke('updateQuery', $filters);
    }

    return $response;
  }

}
