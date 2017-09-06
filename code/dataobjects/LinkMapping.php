<?php
/**
 * A link mapping that connects a link to either a redirected link or another
 * page on the site.
 *
 * @package silverstripe-linkmapping
 */
class LinkMapping extends DataObject {

	public static $db = array(
		'MappedLink'   => 'Varchar(255)',
		'RedirectType' => 'Enum("Page, Link", "Page")',
		'RedirectLink' => 'Varchar(255)'
	);

	public static $has_one = array(
		'RedirectPage' => 'SiteTree'
	);

	public static $summary_fields = array(
		'MappedLink',
		'RedirectType',
		'RedirectLink',
		'RedirectPage.Title'
	);
	
	public $export_fields = array(
		'MappedLink'   => 'MappedLink',
		'RedirectType' => 'RedirectType',
		'RedirectLink' => 'RedirectLink',
		'RedirectPageID' => 'RedirectPageID'
	);

	public static $searchable_fields = array(
		'MappedLink'   => array('filter' => 'PartialMatchFilter'),
		'RedirectType' => array('filter' => 'ExactMatchFilter')
	);

	/**
	 * Returns a link mapping for a link if one exists.
	 *
	 * @param  string $link
	 * @return LinkMapping
	 */
	public static function get_by_link($link) {
		$link = Convert::raw2sql(self::unify_link($link));

		// check for an exact match
		$match = LinkMapping::get()->filter('MappedLink', $link)->first();
		if($match) return $match;
	
		// check for a match with the same get vars in a different order
		if(strpos($link, '?')){
			$linkParts 		= explode('?', $link);
			$url 			= $linkParts[0];
			$matches		= LinkMapping::get()->where("MappedLink like '$url%'");
			parse_str($linkParts[1], $queryParams);			

			if($matches->count()){
				foreach ($matches as $match) {

					$matchQueryString = explode('?', $match->MappedLink);

          // check for ignore get parameters, 
          // then check if the url matches it completely, except for the get parameters
          // -- this check verifies urls like "link/to?param1=value1&param2=value2" doesn't match to "link/towards/other/page"
          
          if($match->IgnoreGetParameters && $match->MappedLink == $matchQueryString[0]){ return $match; }

					parse_str($matchQueryString[1], $matchParams); 
					if($matchParams == $queryParams){
						return $match;
					}		
				}
			}

		}

    
	}

	/**
	 * Unifies a link so mappings are predictable.
	 *
	 * @param  string $link
	 * @return string
	 */
	public static function unify_link($link) {
		return strtolower(trim(trim(Director::makeRelative($link), '/'), '?'));
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('RedirectType');
		$fields->removeByName('RedirectLink');
		$fields->removeByName('RedirectPageID');

		$fields->insertBefore(new HeaderField(
			'MappedLinkHeader', $this->fieldLabel('MappedLinkHeader')
		), 'MappedLink');

		$pageLabel = $this->fieldLabel('RedirectToPage');
		$linkLabel = $this->fieldLabel('RedirectToLink');

		$fields->addFieldToTab('Root.Main', new HeaderField(
			'RedirectToHeader', $this->fieldLabel('RedirectToHeader')
		));
		$fields->addFieldToTab('Root.Main', new SelectionGroup('RedirectType', array(
			"Page//$pageLabel" => new TreeDropdownField('RedirectPageID', '', 'Page'),
			"Link//$linkLabel" => new TextField('RedirectLink', '')
		)));

		return $fields;
	}

	public function fieldLabels($includerelations = true) {
		return parent::fieldLabels($includerelations) + array(
			'MappedLinkHeader' => _t('LinkMapping.MAPPEDLINK', 'Mapped Link'),
			'RedirectToHeader' => _t('LinkMapping.REDIRECTTO', 'Redirect To'),
			'RedirectionType'  => _t('LinkMapping.REDIRECTIONTYPE', 'Redirection type'),
			'RedirectToPage'   => _t('LinkMapping.REDIRTOPAGE', 'Redirect to a page'),
			'RedirectToLink'   => _t('LinkMapping.REDIRTOLINK', 'Redirect to a link')
		);
	}

	/**
	 * @return string
	 */
	public function getLink() {
		if ($this->RedirectType == 'Page' && $this->RedirectPageID) {
			return $this->RedirectPage()->Link();
		} else {
			return Controller::join_links(Director::baseURL(), $this->RedirectLink);
		}
	}

}
