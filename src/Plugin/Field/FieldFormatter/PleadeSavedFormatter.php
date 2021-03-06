<?php

namespace Drupal\pleade\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Plugin implementation of the Pleade Saved formatter.
 *
 * @FieldFormatter(
 *   id = "pleade_saved_formatter",
 *   label = @Translation("Pleade Saved Formatter"),
 *   field_types = {
 *     "pleade_saved"
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class PleadeSavedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
	public function viewElements(FieldItemListInterface $items, $langcode) {
		$elements = [];
	
		foreach ($items as $delta => $item){
			// variables to send to Pleade
			$saved_id = $item->saved_id;
			$drupal_title = $item->drupal_title;
			$items_number = $item->items_number;
			
			// results markup from Pleade to print
			$markups = $this->getSavedMarkupFromPleade($saved_id, $drupal_title, $items_number);
			
			// results final
			$elements[$delta] = [
					'#theme' => 'pleade_saved_formatter',
					'#saved_id' => $saved_id,
					'#drupal_title' => $drupal_title,
					'#items_number' => $items_number,
					'#type' => 'markup',
					'#markups' => $markups,
			];
		}
	
		return $elements;
	}

	/**
	 * Return html value for a saved or a federated form from Pleade
	 */
	public function getSavedMarkupFromPleade($saved_id, $drupal_title, $items_number){
		// Initialisation
		$markup; // contains markup to return
		$client = \Drupal::httpClient(); // main HTTP object
		$pleade_url; // contain the url for quering Pleade
		$request; //request object for quering Pleade
		$response; // Contain the response after quering Pleade
		$hresponse = NULL; // contient la réponse au format html
	
		// Avoid Drupal cache problems due to Pleade's sessions cookie
		\Drupal::service('page_cache_kill_switch')->trigger();
	
		// get type of saved or form, real id and pleade url to retrieve
		$type = 'savedsearch';
		$pid = '';
		$pleade_url = \Drupal::request()->getSchemeAndHttpHost();
		if( strpos($saved_id, '::SavedSearch::') !== false ) {
			$type = 'savedsearch';
			$pid = explode('::SavedSearch::', $saved_id)[1];
			$pleade_url = $pleade_url . '/pleade/getSavedSearch.ajax-html?id=' . $pid . '&title=' . urlencode($drupal_title) . '&hpp=' . $items_number;
		}
		else if( strpos($saved_id, '::SavedBasket::') !== false ) {
			$type = 'savedbasket';
			$pid = explode('::SavedBasket::', $saved_id)[1];
			$pleade_url = $pleade_url . '/pleade/getSavedBasket.ajax-html?id=' . $pid . '&title=' . urlencode($drupal_title) . '&hpp=' . $items_number;
		}
		else if( strpos($saved_id, '::FederateSearchForm::') !== false ) {
			$type = 'federateform';
			$pid = explode('::FederateSearchForm::', $saved_id)[1];
			$pleade_url = $pleade_url . '/pleade/embed/' . $pid . '-search-form.xsp';
		}
		
		// process pleade request.
		try {
			$request = $client->get($pleade_url, $this->getHeaders());
			$response = $request->getBody();
		} catch (RequestException $e) {
			\Drupal::logger('pleade')->error($e->getMessage());
		}
	
		// transfrom response to string and clean trailing xml tags
		$markup = (string) $response;
		
		return $markup;
	}
	
	/**
	 * get headers and cookies
	 * @return [type] [description]
	 */
	protected function getHeaders() {
		$headers = array();
		if(isset($_SERVER['HTTP_COOKIE'])){
			$headers['headers'] = array(
					'Cookie' => $_SERVER['HTTP_COOKIE'],
			);
		}
		return $headers;
	}
	
	
}
