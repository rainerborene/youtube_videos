<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	class fieldYouTube_Video extends Field {

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct(&$parent) {
			parent::__construct($parent);

			$this->_name = 'YouTube Video';
			$this->_required = false;

			$this->set('required', 'no');
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`video_id` varchar(11) default NULL,
					`title` varchar(255) default NULL,
					`description` text,
					`keywords` text,
					`duration` int(11) unsigned NOT NULL,
					`favorites` int(11) unsigned NOT NULL,
					`views` int(11) unsigned NOT NULL,
					`user_name` varchar(255) default NULL,
					`user_url` varchar(255) default NULL,
					`published_date` int(11) unsigned NOT NULL,
					`last_updated` int(11) unsigned NOT NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				);"
			);
		}

		public function allowDatasourceOutputGrouping() {
			return false;
		}

		public function allowDatasourceParamOutput() {
			return false;
		}

		public function canFilter() {
			return true;
		}

		public function canPrePopulate() {
			return false;
		}

		public function isSortable() {
			return true;
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
	
		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);

			$label = Widget::Label('Update cache (minutes; leave blank to never update) <i>Optional</i>');
			$label->appendChild(Widget::Input("fields[{$this->get('sortorder')}][refresh]", $this->get('refresh')));

			$wrapper->appendChild($label);
			
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$value = General::sanitize($data['video_id']);
			$label = Widget::Label($this->get('label'));

			$video_id = new XMLElement('input');
			$video_id->setAttribute('type', 'text');
			$video_id->setAttribute('name', 'fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix);
			$video_id->setAttribute('value', $value);

			if ($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

			if (strlen($value) == 11 && is_null($flagWithError)) {
				$video_id->setAttribute('class', 'hidden');
				$video_container = new XMLElement('span');

				$video_url = 'http://www.youtube.com/v/' . $value;

				$video = new XMLElement('object');
				$video->setAttribute('width', 560);
				$video->setAttribute('height', 340);

				$param = new XMLElement('param');
				$param->setAttribute('allowfullscreen', 'true');
				$video->appendChild($param);

				$param = new XMLElement('param');
				$param->setAttribute('allowscriptaccess', 'always');
				$video->appendChild($param);

				$param = new XMLElement('param');
				$param->setAttribute('movie', $video_url);
				$video->appendChild($param);

				$embed = new XMLElement('embed');
				$embed->setAttribute('src', $video_url);
				$embed->setAttribute('allowfullscreen', 'true');
				$embed->setAttribute('allowscriptaccess', 'always');
				$embed->setAttribute('width', 560);
				$embed->setAttribute('height', 340);
				$embed->setAttribute('type', 'application/x-shockwave-flash');

				$video->appendChild($embed);
				$video_container->appendChild($video);

				$description = new XMLElement('div');
				$description->setAttribute('class', 'description');
				$description->setValue($data['title'] . ' by <a href="' . $data['user_url'] . '" target="blank">' . $data['user_name'] . '</a> (' . $data['views'] . ' views)');

				$change = new XMLElement('a', 'Remove Video');
				$change->setAttribute('class', 'change');
				$description->appendChild($change);

				$video_container->appendChild($description);

				$label->appendChild($video_container);
			}

			$label->appendChild($video_id);

			if ($flagWithError != NULL)
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else
				$wrapper->appendChild($label);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			
			if (empty($data))
				return array();

			$data = self::parseData($data);

			$video_id = self::getVideoId($data);
			$result = self::getVideoInfo($video_id);

			if (!is_array($result)) {
				$message = __("Failed to load clip XML");
				$status = self::__MISSING_FIELDS__;
				return;
			}

			return $result;
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}	

			/*
				$video_id = self::getVideoId($data);

				if (is_null($video_id) && strlen($data) > 32){
					$message = __("%s must be a valid YouTube video id or video URL", array($this->get('label')));
					return self::__INVALID_FIELDS__;
				}

				$video = self::getVideoInfo($video_id);

				if (!$video && strlen($data) > 32){
					$message = __("Failed to load video XML");
					return self::__INVALID_FIELDS__;
				}
			*/

			return self::__OK__;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data) {
			if(!is_array($data) || empty($data)) return;

			// If cache has expired refresh the data array from parsing the API XML
			if ((time() - $data['last_updated']) > ($this->_fields['refresh'] * 60)){
				$data = self::updateVideoInfo($data['video_id'], $this->_fields['id'], $wrapper->getAttribute('id'), $this->Database);
			}

			$video = new XMLElement($this->get('element_name'));

			$video->setAttributeArray(array(
				'video-id' => $data['video_id'],
				'duration' => $data['duration'],
				'favorites' => $data['favorites'],
				'views' => $data['views']
			));

			$video->appendChild(new XMLElement('title', General::sanitize($data['title'])));
			$video->appendChild(new XMLElement('description', General::sanitize($data['description'])));

			$author = new XMLElement('author');
			$author->appendChild(new XMLElement('name', General::sanitize($data['user_name'])));
			$author->appendChild(new XMLElement('url', $data['user_url']));

			$video->appendChild($author);
			$wrapper->appendChild($video);
		}

		public function prepareTableValue($data, XMLElement $link=NULL){
			if (strlen($data['video_id']) == 0) return NULL;

			$image = '<img src="http://i.ytimg.com/vi/' . $data['video_id'] . '/1.jpg" width="120" height="90" />';

			if ($link) {
				$link->setValue($image);
				return $link->generate();
			} else {
				$link = new XMLElement('span', $image . '<br />' . $data['views'] . ' views');
				return $link->generate();
			}
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$refresh = $this->get('refresh');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			$fields['refresh'] = is_null($refresh) ? '0' : $refresh;

			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");

			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

	/*-------------------------------------------------------------------------
		Helpers:
	-------------------------------------------------------------------------*/

		public static function parseData($data) {
			if (parse_url($data)) return $data;

			if (strlen($data) == 11) {
				return "http://www.youtube.com/watch?v={$data}";
			}

			return NULL;
		}

		public static function getVideoId($data) {
			$url = parse_url($data);

			if (is_array($url) && preg_match('/youtube\.com/i', $url['host'])){
				if (preg_match('/v=(?<id>[a-z0-9-_]+)/i', $url['query'], $match)){
					return $match['id'];
				}
			}
	 	}

		public static function getVideoFeed($video_id) {
			return DOMDocument::load("http://gdata.youtube.com/feeds/api/videos/{$video_id}");
		}

		public static function getVideoInfo($video_id) {
			// namespaces
			$ns = array();
			$ns['media'] = 'http://search.yahoo.com/mrss/';
			$ns['yt'] = 'http://gdata.youtube.com/schemas/2007';

			// response xml
			$video = self::getVideoFeed($video_id);

			if (!$video) return;

			$entry = $video->getElementsByTagName('entry')->item(0);
			$media = $entry->getElementsByTagNameNS($ns['media'], 'group')->item(0);
			$author = $entry->getElementsByTagName('author')->item(0);
			$statistics = $entry->getElementsByTagNameNS($ns['yt'], 'statistics')->item(0);

			$data = array(
				'video_id' => $video_id,
				'title' => $entry->getElementsByTagName('title')->item(0)->nodeValue,
				'description' => $entry->getElementsByTagName('content')->item(0)->nodeValue,
				'keywords' => $media->getElementsByTagNameNS($ns['media'], 'keywords')->item(0)->nodeValue,
				'duration' => $media->getElementsByTagNameNS($ns['yt'], 'duration')->item(0)->getAttribute('seconds'),
				'favorites' => $statistics->getAttribute('favoriteCount'),
				'views' => $statistics->getAttribute('viewCount'),
				'user_name' => $author->getElementsByTagName('name')->item(0)->nodeValue,
				'user_url' => 'http://www.youtube.com/user/' . strtolower($author->getElementsByTagName('name')->item(0)->nodeValue),
				'published_date' => @strtotime($entry->getElementsByTagName('published')->item(0)->nodeValue),
				'last_updated' => time()
			);

			return $data;
		}

		public static function updateVideoInfo($video_id, $field_id, $entry_id, $database) {
			$data = self::getVideoInfo($clip_id);

			if (!$data) return;

			Symphony::Database()->update($data, "sym_entries_data_{$field_id}", "entry_id={$entry_id}");

			return $data;
		}

	}
