<?php

/*

Контактаня информация:
Руслан Кладько
rkladko@gmail.com
ruslan_kladko@skype

Задача - реализовать виртуальные методы класса embed в производном классе embed_ext
Конструктор класса embed_ext получает на вход URL, по которому находится произвольное видео
на сайте видеохостинга, который вы выбрали в исходном документе с тестовым заданием

*/

abstract class embed
{
	protected $url = '';
	protected $page_content = '';

	public function __construct($url)
	{
		$this->url = $url;
		$this->page_content = $this->get_page($url);

		echo implode('<hr>', array
		(
			$this->url,
			implode('|', $this->id()),
			$this->name(),
			$this->descr(),
			'<img src="'.$this->thumb_url().'">',
			implode('x', $this->size()),
		));
	}

	public function get_page($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page_content = curl_exec($ch);
		curl_close($ch);

		return $page_content;
	}

	// Возвращает список уникальных участков в embed коде видео, в виде массива
	abstract protected function id();
	// Возвращает имя видео
	abstract protected function name();
	// Возвращает описание видео
	abstract protected function descr();
	// Возвращает размеры видео в виде массива-списка (ширина, высота)
	abstract protected function size();
	// Возвращает адрес тамба видео. Если есть несколько адресов тамба, нужно взять тот,
	// размер тамба по которому максимальный
	abstract protected function thumb_url();
}

# Тут ваш код
class embed_ext extends embed {

	// Facebook Graph API params
	protected $fb_graph_api = 'https://graph.facebook.com/';
	protected $fb_token = 'AAACEdEose0cBAMGq3kgwvsWx9phAvN7DcztMtig7ZAr7Dl6B5S2OeGEGgTfz8LLsIZAv60ZAHOVJmYqxBmZCbhbIdacZAuabPAJJMsKjCwjT2ZCPrBUwZCM';

	protected $video_info = array();
	protected $embed_info = array();

	public function __construct($url) {
		$graph_url = $this->getGraphURL($url);
		$this->video_info = json_decode($this->get_page($graph_url), true);
		$this->embed_info = $this->getEmbedInfo();
		parent::__construct($url);
	}

	protected function getEmbedInfo() {
		$out = array();
		if ($embed_html = $this->getInfo('embed_html')) {
			$frame = simplexml_load_string($embed_html);
			$out = (array)$frame->attributes();
			$out = $out['@attributes'];
		}
		return $out;
	}

	protected function getVideoId($url) {
		$query = parse_url($url, PHP_URL_QUERY);
		parse_str($query, $args);
		return $args['v'];
	}

	protected function getGraphURL($url) {
		$graph_url = $this->fb_graph_api . $this->getVideoId($url);
		$query_data = array('access_token' => $this->fb_token);
		return $graph_url . '?' . http_build_query($query_data);
	}

	protected function getInfo($key) {
		if (isset($this->video_info[$key])) {
			return $this->video_info[$key];
		}
		return false;
	}

	// Возвращает список уникальных участков в embed коде видео, в виде массива
	protected function id() {
		return array_unique(array_keys($this->embed_info));
	}

	// Возвращает имя видео
	protected function name() {
		return $this->getInfo('name');
	}

	// Возвращает описание видео
	protected function descr() {
		return $this->getInfo('description');
	}

	// Возвращает размеры видео в виде массива-списка (ширина, высота)
	protected function size() {
		return array($this->embed_info['width'], $this->embed_info['height']);
	}

	// Возвращает адрес тамба видео. Если есть несколько адресов тамба, нужно взять тот,
	// размер тамба по которому максимальный
	protected function thumb_url() {
		return $this->getInfo('picture');
	}
}

// Выводим данные на экран
$embed = new embed_ext('http://www.facebook.com/photo.php?v=109148712614540');

?>