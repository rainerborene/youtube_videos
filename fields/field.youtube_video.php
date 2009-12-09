<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(EXTENSIONS . '/youtube_videos/lib/youtube_helper.php');

	class FieldYouTube_Video extends Field {

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'YouTube Video';
			$this->_required = false;

			$this->set('required', 'no');
		}

		public function isSortable(){
			return true;
		}

		public function canFilter(){
			return true;
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if ($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			$video_id = YouTubeHelper::getVideoId($data);

			if (is_null($video_id) && strlen($data) > 32){
				$message = __("%s must be a valid YouTube video id or video URL", array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}

			$video = YouTubeHelper::getVideoInfo($video_id);

			if (!$video && strlen($data) > 32){
				$message = __("Failed to load video XML");
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			if (strlen($data) <= 32) return array();

			$status = self::__OK__;

			$result = YouTubeHelper::getVideoInfo(YouTubeHelper::getVideoId($data));

			// HACK: couldn't figure out how to validate in checkPostFieldData() and then prevent
			// this processRawFieldData function executing, since it requires valid data to load the XML
			if (!is_array($result)) {
				$message = __("Failed to load clip XML");
				$status = self::__MISSING_FIELDS__;
				return;
			}

			return $result;
		}

		public function appendFormattedElement(&$wrapper, $data) {
			if(!is_array($data) || empty($data)) return;

			// If cache has expired refresh the data array from parsing the API XML
			if ((time() - $data['last_updated']) > ($this->_fields['refresh'] * 60)){
				$data = YouTubeHelper::updateVideoInfo($data['video_id'], $this->_fields['id'], $wrapper->getAttribute('id'), $this->Database);
			}

			$video = new XMLElement($this->get('element_name'));

			$video->setAttributeArray(array(
				'video-id' => $data['video_id'],
				'duration' => $data['duration'],
				'favorites' => $data['favorites'],
				'views' => $data['views']
			));

			$video->appendChild(new XMLElement('title', $data['title']));
			$video->appendChild(new XMLElement('description', $data['description']));

			$author = new XMLElement('author');
			$author->appendChild(new XMLElement('name', $data['user_name']));
			$author->appendChild(new XMLElement('url', $data['user_url']));

			$video->appendChild($author);
			$wrapper->appendChild($video);
		}

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$value = General::sanitize($data['video_id']);
			$source = "http://www.youtube.com/watch/?v={$value}";
			$label = Widget::Label($this->get('label'));

			$video_id = new XMLElement('input');
			$video_id->setAttribute('type', 'text');
			$video_id->setAttribute('name', 'fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix);
			$video_id->setAttribute('value', $source);

			if (strlen($value) == 0 || $flagWithError != NULL){

				if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', 'Optional'));

			} else {

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
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$wrapper->appendChild(new XMLElement('h4', $this->get('label') . ' <i>'.$this->Name().'</i>'));
			$label = Widget::Label('Video ID');
			$label->appendChild(Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : NULL)));
			$wrapper->appendChild($label);
		}

		public function prepareTableValue($data, XMLElement $link=NULL){
			if(strlen($data['video_id']) == 0) return NULL;

			$image = '<img src="http://i.ytimg.com/vi/' . $data['video_id'] . '/1.jpg" width="120" height="90"/>';

			if($link){
				$link->setValue($image);
				return $link->generate();
			} else {
				$link = new XMLElement('span', $image . '<br />' . $data['views'] . ' views');
				return $link->generate();
			}
		}

		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);

			$label = Widget::Label('Update cache (minutes; leave blank to never update) <i>Optional</i>');
			$label->appendChild(Widget::Input('fields[' . $this->get('sortorder') . '][refresh]', $this->get('refresh')));
			$wrapper->appendChild($label);
		}

		function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "INNER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (strtolower($order) == 'random' ? 'RAND()' : "`ed`.`views` $order");
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.video_id REGEXP '{$pattern}'
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.video_id = '{$value}'
					";
				}
			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.video_id IN ('{$data}')
				";
			}

			return true;
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$refresh = $this->get('refresh');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			$fields['refresh'] = $refresh;

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
		}

		public function createTable(){
			return $this->_engine->Database->query(
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

	}
