<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 코어 및 모든 모듈에서 첨부파일과 관련된 모든 기능을 제어한다.
 *
 * @file /modules/attachment/ModuleAttachment.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 12. 31.
 */
class ModuleAttachment {
	/**
	 * iModule 및 Module 코어클래스
	 */
	private $IM;
	private $Module;

	/**
	 * DB 관련 변수정의
	 *
	 * @private object $DB DB접속객체
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;

	/**
	 * 언어셋을 정의한다.
	 *
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;

	/**
	 * 첨부파일 설정변수
	 */
	private $_id = null;
	private $_name = 'attachments';
	private $_templet = '#';
	private $_templet_file = null;
	private $_module = null;
	private $_target = null;
	private $_wysiwyg = false;
	private $_wysiwygOnly = false;
	private $_buttonText = null;
	private $_loader = null;
	private $_disabled = false;
	private $_accept = '*';
	private $_deleteMode = 'AUTO';
	private $_currentPath = null;

	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @private object $files 파일정보
	 */
	private $files = array();

	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param Module $Module Module 코어클래스
	 * @see /classes/iModule.class.php
	 * @see /classes/Module.class.php
	 */
	function __construct($IM,$Module) {
		/**
		 * iModule 및 Module 코어 선언
		 */
		$this->IM = $IM;
		$this->Module = $Module;

		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see 모듈폴더의 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->attachment = 'attachment_table';

		$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/style.css');
	}

	/**
	 * 모듈 코어 클래스를 반환한다.
	 * 현재 모듈의 각종 설정값이나 모듈의 package.json 설정값을 모듈 코어 클래스를 통해 확인할 수 있다.
	 *
	 * @return Module $Module
	 */
	function getModule() {
		return $this->Module;
	}

	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getModule()->getInstalled()->database);
		return $this->DB;
	}

	/**
	 * 모듈에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}

	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $protocol API 호출 프로토콜 (get, post, put, delete)
	 * @param string $api API명
	 * @param any $idx API 호출대상 고유값
	 * @param object $params API 호출시 전달된 파라메터
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($protocol,$api,$idx=null,$params=null) {
		$data = new stdClass();

		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeGetApi',$this->getModule()->getName(),$api,$values);

		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php';
		}

		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterGetApi',$this->getModule()->getName(),$api,$values,$data);

		return $data;
	}

	/**
	 * [사이트관리자] 모듈 설정패널을 구성한다.
	 *
	 * @return string $panel 설정패널 HTML
	 */
	function getConfigPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this->getModule();

		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/configs.php';
		$panel = ob_get_contents();
		ob_end_clean();

		return $panel;
	}

	/**
	 * [사이트관리자] 모듈 관리자패널 구성한다.
	 *
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this;

		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/index.php';
		$panel = ob_get_contents();
		ob_end_clean();

		return $panel;
	}

	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (is_file($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language && is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}

		$returnString = null;
		$temp = explode('/',$code);

		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}

		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}

			if ($string != null) $returnString = $string;
		}

		$this->IM->fireEvent('afterGetText',$this->getModule()->getName(),$code,$returnString);

		/**
		 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}

	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);

		$description = null;
		switch ($code) {
			default :
				if (is_object($value) == false && $value) $description = $value;
		}

		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		$error->type = 'BACK';

		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}

	/**
	 * 템플릿 정보를 가져온다.
	 *
	 * @param string $this->getTemplet($configs) 템플릿명
	 * @return string $package 템플릿 정보
	 */
	function getTemplet($templet=null) {
		$templet = $templet == null ? '#' : $templet;
		$templet_configs = null;

		/**
		 * 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정일 경우
		 */
		if (is_object($templet) == true) {
			$templet = $templet !== null && isset($templet->templet) == true ? $templet->templet : '#';
			$templet_configs = $templet !== null && isset($templet->templet_configs) == true ? $templet->templet_configs : null;
		}

		/**
		 * 템플릿명이 # 이면 모듈 기본설정에 설정된 템플릿을 사용한다.
		 */
		if ($templet == '#') {
			$templet = $this->getModule()->getConfig('templet');
			$templet_configs = $this->getModule()->getConfig('templet_configs');
		}

		return $this->getModule()->getTemplet($templet,$templet_configs);
	}

	/**
	 * 파일 삭제 모달을 가져온다.
	 *
	 * @param int $idx 파일고유번호
	 * @return string $html 모달 HTML
	 */
	function getDeleteModal($idx) {
		$title = '파일삭제 확인';

		$file = $this->getFileInfo($idx);

		$content = '<input type="hidden" name="code" value="'.Encoder($idx).'">'.PHP_EOL;
		$content.= '<div data-role="message">'.$file->name.' 파일을 삭제하시겠습니까?</div>';


		$buttons = array();

		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '삭제';
		$button->class = 'danger';
		$buttons[] = $button;

		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;

		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}

	/**
	 * 업로더를 호출한 뒤 업로더관련 변수를 초기화한다.
	 */
	function reset() {
		$this->_id = null;
		$this->_name = 'attachments';
		$this->_templet = '#';
		$this->_templet_file = null;
		$this->_module = null;
		$this->_target = null;
		$this->_wysiwyg = false;
		$this->_buttonText = null;
		$this->_loader = null;
		$this->_disabled = false;
		$this->_accept = '*';
		$this->_deleteMode = 'AUTO';
	}

	/**
	 * 업로더 고유값을 가져온다.
	 *
	 * @return string $id
	 */
	function getId() {
		return $this->_id;
	}

	/**
	 * 업로더 고유값을 설정한다.
	 *
	 * @param string $id
	 * @return Attachment $this
	 */
	function setId($id) {
		$this->_id = $id;

		return $this;
	}

	/**
	 * 업로더 파일필드를 설정한다.
	 *
	 * @param string $id
	 * @return Attachment $this
	 */
	function setName($name) {
		$this->_name = $name;

		return $this;
	}

	/**
	 * 업로더 템플릿을 설정한다.
	 *
	 * @param string $templet
	 * @return Attachment $this
	 */
	function setTemplet($templet) {
		$this->_templet = $templet;

		return $this;
	}

	/**
	 * 업로더 템플릿파일을 설정한다.
	 *
	 * @param string $path
	 * @return Attachment $this
	 */
	function setTempletFile($path) {
		$this->_templet_file = $path;

		return $this;
	}

	/**
	 * 업로더를 사용하는 모듈을 설정한다.
	 *
	 * @param string $module
	 * @return Attachment $this
	 */
	function setModule($module) {
		$this->_module = $module;

		return $this;
	}

	/**
	 * 업로더를 사용하는 대상을 설정한다.
	 *
	 * @param string $target
	 * @return Attachment $this
	 */
	function setTarget($target) {
		$this->_target = $target;

		return $this;
	}

	/**
	 * 업로더가 위지윅에디터와 연동되는지 설정한다.
	 * 위지윅에디터가 설정되면 $target 값이 $wysiwyg 으로 대체된다.
	 *
	 * @param string $wysiwyg 위지윅에디터의 textarea 이름
	 * @return Attachment $this
	 */
	function setWysiwyg($wysiwyg) {
		$this->_target = $wysiwyg;
		$this->_wysiwyg = true;

		return $this;
	}

	/**
	 * 파일을 불러올 주소를 지정한다.
	 *
	 * @param string $url
	 */
	function setLoader($url) {
		$this->_loader = $url;

		return $this;
	}

	/**
	 * 파일추가 버튼 텍스트를 설정한다.
	 *
	 * @param string $text
	 * @return Attachment $this
	 */
	function setButtonText($text) {
		$this->_buttonText = $text;

		return $this;
	}

	/**
	 * 파일 삭제모드를 설정한다.
	 *
	 * @param string $deleteMode (AUTO : 자동삭제, MANUAL : 수동삭제)
	 * @return Attachment $this
	 */
	function setDeleteMode($deleteMode) {
		$this->_deleteMode = $deleteMode;

		return $this;
	}

	/**
	 * 첨부파일의 형식을 제한한다.
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#Limiting_accepted_file_types
	 *
	 * @param string $accept (기본값 : *, 예 : image/*, image/png, .doc 등)
	 * @return Attachment $this
	 */
	function setAccept($accept) {
		$this->_accept = $accept;

		return $this;
	}

	/**
	 * 설정된 버튼 텍스트를 반환한다.
	 *
	 * @return string $text
	 */
	function getButtonText() {
		return $this->_buttonText == null ? '파일추가' : $this->_buttonText;
	}

	/**
	 * 업로더를 사용하기 위한 필수요소를 미리 불러온다.
	 */
	function preload() {
		$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/script.js');

		if ($this->_templet_file == null) {
			$Templet = $this->getTemplet($this->_templet);
			$package = $Templet->getPackage();
			if (isset($package->scripts) == true) {
				foreach ($package->scripts as $script) {
					$this->IM->addHeadResource('script',$Templet->getDir().$script);
				}
			}
			if (isset($package->styles) == true) {
				foreach ($package->styles as $style) {
					$this->IM->addHeadResource('style',$Templet->getDir().$style);
				}
			}
		}
	}

	/**
	 * 업로더를 가져온다.
	 *
	 * @return string $html 업로더 HTML
	 */
	function get() {
		$this->preload();
		if ($this->_disabled == true) return '';

		$this->_id = $this->_id == null ? uniqid('UPLOADER_') : $this->_id;

		$header = PHP_EOL.'<!-- ATTACHMENT MODULE -->'.PHP_EOL;
		$header.= '<div id="'.$this->_id.'" data-role="module" data-name="'.$this->_name.'" data-module="attachment" data-templet="'.$this->getTemplet($this->_templet)->getName().'" data-uploader="TRUE" data-delete-mode="'.$this->_deleteMode.'"';
		if ($this->_module != null) $header.= ' data-uploader-module="'.$this->_module.'"';
		if ($this->_target != null) $header.= ' data-uploader-target="'.$this->_target.'"';
		if ($this->_loader != null) $header.= ' data-uploader-loader="'.$this->_loader.'"';
		$header.= ' data-uploader-wysiwyg="'.($this->_wysiwyg == true ? 'TRUE' : 'FALSE').'"';
		$header.= '>'.PHP_EOL;
		$header.= '<div style="display:none;"><input type="file" title="파일첨부" accept="'.$this->_accept.'" multiple></div>'.PHP_EOL;
		$footer = PHP_EOL.'<script>$(document).ready(function() { Attachment.init("'.$this->_id.'"); });</script>'.PHP_EOL;
		$footer.= '</div>';
		$footer.= '<!--// ATTACHMENT MODULE -->'.PHP_EOL;

		/**
		 * 템플릿파일을 호출한다.
		 */
		if ($this->_templet_file == null) {
			$html = $this->getTemplet($this->_templet)->getContext('index',get_defined_vars(),$header,$footer);
		} else {
			$html = $this->getTemplet($this->_templet)->getExternal($this->_templet_file,get_defined_vars(),$header,$footer);
		}

		$this->reset();
		return $html;
	}

	/**
	 * 업로더를 비활성화 한다.
	 *
	 * @return ModuleAttachment $this
	 */
	function disable() {
		$this->_disabled = true;
		return $this;
	}

	/**
	 * 현재 객체를 복사한다.
	 *
	 * @return ModuleAttachment $this
	 */
	function copy() {
		$copy = unserialize(serialize($this));
		return $copy;
	}

	/**
	 * 업로더를 출력한다.
	 */
	function doLayout() {
		echo $this->get();
	}

	private function _buildScript() {
		$processUrl = $this->IM->getProcessUrl('attachment','upload');
		$configs = array();
		$configs['module'] = $this->_module != null ? $this->_module : '';
		$configs['target'] = $this->_target != null ? $this->_target : '';
		$configs['wysiwyg'] = $this->_wysiwyg == true;

		$script = PHP_EOL;
		$script.= '<script>'.PHP_EOL;
		$script.= '$(document).ready(function() {'.PHP_EOL;
		$script.= 'Attachment.init("'.$this->_id.'",'.json_encode($configs).');'.PHP_EOL;

		if (empty($this->_loadFile) == false) {
			$script.= '    Attachment.loadFile("'.$this->_id.'","'.Encoder(json_encode($this->_loadFile)).'");'.PHP_EOL;
		}

		$script.= '});'.PHP_EOL;

		$script.= '</script>'.PHP_EOL;

		return $script;
	}
	
	/**
	 * 첨부파일 경로를 가져온다.
	 *
	 * @param boolean $isFullPath 전체경로포함여부
	 * @return string $path
	 */
	function getAttachmentPath() {
		return $this->IM->getAttachmentPath();
	}

	/**
	 * 첨부파일폴더를 지정한다.
	 *
	 * @param string $path
	 * @return ModuleAttachment $this
	 */
	function setCurrentPath($path=null) {
		$this->_currentPath = $path;

		return $this;
	}

	/**
	 * 하나의 첨부파일폴더에 너무 많은 파일이 저장되는 것을 방지하기 위해 매달 새로운 폴더를 생성하고 해당 경로를 반환한다.
	 *
	 * @param boolean $isFullPath __IM_PATH__ 를 포함한 전체경로를 반환할 지 여부(기본값 : false)
	 * @param int $reg_date 폴더를 생성할 기준시각
	 * @return string $path
	 */
	function getCurrentPath($isFullPath=false,$reg_date=null) {
		$folder = $this->_currentPath ? $this->_currentPath : date('Ym',$reg_date == null ? time() : $reg_date);
		if (is_dir($this->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->getAttachmentPath().'/'.$folder);
			chmod($this->getAttachmentPath().'/'.$folder,0707);
		}

		if ($isFullPath == true) $folder = $this->getAttachmentPath().'/'.$folder;
		return $folder;
	}

	/**
	 * 첨부파일 임시폴더 상대경로를 가져온다.
	 *
	 * @param boolean $isFullPath 전체경로포함여부
	 * @return string $path
	 */
	function getTempDir($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->getAttachmentPath().'/'.$folder);
			chmod($this->getAttachmentPath().'/'.$folder,0707);
		}

		if ($isFullPath == true) $folder = $this->IM->getAttachmentDir().'/'.$folder;
		return $folder;
	}

	/**
	 * 첨부파일 임시폴더 경로를 가져온다.
	 *
	 * @param boolean $isFullPath 전체경로포함여부
	 * @return string $path
	 */
	function getTempPath($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->getAttachmentPath().'/'.$folder);
			chmod($this->getAttachmentPath().'/'.$folder,0707);
		}

		if ($isFullPath == true) $folder = $this->getAttachmentPath().'/'.$folder;
		return $folder;
	}

	/**
	 * 사용할 수 있는 임시파일명을 가져온다.
	 *
	 * @param boolean $isFullPath 전체경로여부
	 * @return string $tempFilePath
	 */
	function getTempFile($isFullPath=false) {
		while (true) {
			$hash = md5(time().rand(10000000,99999999));
			if (is_file($this->getTempPath(true).'/'.$hash) == false) break;
		}

		return $this->getTempPath($isFullPath).'/'.$hash;
	}
	
	/**
	 * 파일명으로 쓸수없는 문자열을 치환한다.
	 *
	 * @param string $filename
	 * @return string $filename
	 */
	function getSafeFileName($filename) {
		return str_replace(array('\\','/',':','*','?','"','<','>','|'),array('','-','-','',"'",'[',']','-'),$filename);
	}
	
	function getFileExtraInfo($idx,$param=null) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		$extra = $file->extra == '' ? null : json_decode($file->extra);

		if ($extra == null || $param == null) return $extra;
		if ($param != null && !empty($extra->$param)) return $extra->$param;
		else return $extra;
	}

	function setFileExtraInfo($idx,$param,$value=null,$isReplace=false) {
		if ($isReplace == true) {
			$extra = new stdClass();
		} else {
			$extra = $this->getFileExtraInfo($idx);
			if ($extra == null) {
				$extra = new stdClass();
			}
		}
		if ($value == null && isset($extra->$param) == true) {
			unset($extra->$param);
		} else {
			$extra->$param = $value;
		}

		$extra = json_encode($extra,JSON_UNESCAPED_UNICODE);
		$this->db()->update($this->table->attachment,array('extra'=>$extra))->where('idx',$idx)->execute();
	}
	
	/**
	 * 특정경로에 있는 파일의 MIME 값을 읽어온다.
	 *
	 * @param string $path 파일절대경로
	 * @return string $mime 파일 MIME
	 */
	function getFileMime($path) {
		if (is_file($path) == true) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo,$path);
			finfo_close($finfo);

			return $mime;
		} else {
			return false;
		}
	}
	
	/**
	 * 파일의 MIME 값을 이용하여 파일종류를 정리한다.
	 *
	 * @param string $mime 파일 MIME
	 * @return string $type 파일종류
	 */
	function getFileType($mime) {
		$type = 'file';
		if ($mime == 'image/svg+xml') {
			$type = 'svg';
		} elseif ($mime == 'image/x-icon') {
			$type = 'icon';
		} elseif (preg_match('/application\/vnd.openxmlformats\-officedocument/',$mime) == true || $mime == 'application/CDFV2-corrupt' || $mime == 'application/pdf') {
			$type = 'document';
		} elseif (preg_match('/text\//',$mime) == true) {
			$type = 'text';
		} elseif (preg_match('/^image\/(jpeg|png|gif)/',$mime) == true) {
			$type = 'image';
		} elseif (preg_match('/^video/',$mime) == true) {
			$type = 'video';
		} elseif (preg_match('/^audio/',$mime) == true) {
			$type = 'audio';
		} elseif (preg_match('/application\/(zip|gzip|x\-rar\-compressed|x\-gzip)/',$mime) == true) {
			$type = 'archive';
		}

		return $type;
	}
	
	/**
	 * 파일의 확장자만 가져온다.
	 *
	 * @param string $filename 파일명
	 * @param string $filepath 파일절대경로 (파일절대경로가 존재할 경우, 실제 파일의 확장자를 가져온다.)
	 * @return string $extension 파일 확장자
	 */
	function getFileExtension($filename,$filepath='') {
		return strtolower(pathinfo($filename,PATHINFO_EXTENSION));
	}

	function getPreviewHtml($filename,$filepath) {

	}

	/**
	 * 실제 서버상의 경로는 보안을 위하여 숨기고, 유저가 접근할 수 있는 파일경로(URL)을 반환한다.
	 *
	 * @param int $idx 파일주소를 가져올 파일고유번호
	 * @param string $view 접근하는 방식 (view : 웹페이지 상에 embed 되기 위한 주소, thumbnail : 웹페이지 상에서 embed 되기 위한 썸네일 주소, download : 파일종류와 무관하게 무조건 다운로드되는 주소)
	 * @return string $url
	 */
	function getAttachmentUrl($idx,$view='view',$isFullUrl=false) {
		if (is_object($idx) == true) {
			$file = $idx;
		} else {
			$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		}

		if ($isFullUrl == true) {
			$url = IsHttps() == true ? 'https://' : 'http://';
			$url.= $_SERVER['HTTP_HOST'].__IM_DIR__;
		} else {
			$url = __IM_DIR__;
		}

		if ($file == null) {
			return null;
		} else {
			if ($view == 'view') return $url.'/attachment/view/'.$file->idx.'/'.urlencode($file->name);
			if ($view == 'thumbnail') {
				if ($file->type == 'image') return $url.'/attachment/thumbnail/'.$file->idx.'/'.urlencode($file->name);
				elseif (file_exists($this->getAttachmentPath().'/'.$file->path.'.thumb') == true) return $url.'/attachment/thumbnail/'.$file->idx.'/'.urlencode($file->name).'.jpg';
				else return null;
			}

			return $url.'/attachment/'.$view.'/'.$file->idx.'/'.urlencode($file->name);
		}
	}

	/**
	 * 썸네일을 생성한다.
	 *
	 * @param string $imgPath 썸네일을 생성할 대상 이미지 경로
	 * @param string $thumbPath 썸네일이 저장될 경로
	 * @param int $width 썸네일 가로크기 (0 일 경우 지정된 썸네일 세로크기에 맞춰 동일비율로 축소한다. 가로크기 및 세로크기가 모두 0이 될 수는 없다.)
	 * @param int $height 썸네일 세로크기 (0 일 경우 지정된 썸네일 가로크기에 맞춰 동일비율로 축소한다. 가로크기 및 세로크기가 모두 0이 될 수는 없다.)
	 * @param boolean $is_delete 원본 이미지파일을 삭제할 지 여부
	 * @param string $forceType 원본 이미지의 포맷과 무관하게 썸네일의 이미지포맷(JPG, GIF, PNG)를 지정할 경우 해당 포맷명
	 * @return boolean $success
	 */
	function createThumbnail($imgPath,$thumbPath,$width,$height,$delete=false,$forceType=null) {
		$result = true;
		$imginfo = @getimagesize($imgPath);
		$extName = $imginfo[2];

		switch($extName) {
			case '2' :
				$src = @ImageCreateFromJPEG($imgPath) or $result = false;
				$type = 'jpg';
				break;
			case '1' :
				$src = @ImageCreateFromGIF($imgPath) or $result = false;
				$type = 'gif';
				break;
			case '3' :
				$src = @ImageCreateFromPNG($imgPath) or $result = false;
				$type = 'png';
				break;
			default :
				$result = false;
		}

		if ($result == true) {
			if ($width == 0) {
				$width = ceil($height*$imginfo[0]/$imginfo[1]);
			}

			if ($height == 0) {
				$height = $width*$imginfo[1]/$imginfo[0];
			}

			if ($imginfo[0] == $width && $imginfo[1] == $height) {
				@copy($imgPath,$thumbPath);
				if ($delete == true) @unlink($imgPath);
				return true;
			}

			$thumb = @ImageCreateTrueColor($width,$height);

			switch ($type) {
				case 'png':
					$background = imagecolorallocate($src,0,0,0);
					imagecolortransparent($thumb,$background);
					imagealphablending($thumb,false);
					imagesavealpha($thumb,true);
					break;

				case 'gif':
					$background = imagecolorallocate($src, 0, 0, 0);
					imagecolortransparent($src, $background);
					break;
			}

			@ImageCopyResampled($thumb,$src,0,0,0,0,$width,$height,@ImageSX($src),@ImageSY($src)) or $result = false;

			$type = $forceType != null ? $forceType : $type;

			if ($type == 'jpg') {
				@ImageJPEG($thumb,$thumbPath,100) or $result = false;
			} elseif($type == 'gif') {
				@ImageGIF($thumb,$thumbPath,100) or $result = false;
			} elseif($type == 'png') {
				@imagePNG($thumb,$thumbPath) or $result = false;
			} else {
				$result = false;
			}
			@ImageDestroy($src);
			@ImageDestroy($thumb);
			@chmod($thumbPath,0755);
		}

		if ($delete == true) {
			@unlink($imgPath);
		}

		return $result;
	}

	/**
	 * 썸네일을 생성할때 지정된 가로 및 세로크기에 맞춰 비율에 따라 원본이미지를 자른 후 저장한다.
	 *
	 * @param string $imgPath 썸네일을 생성할 대상 이미지 경로
	 * @param string $thumbPath 썸네일이 저장될 경로
	 * @param int $width 썸네일 가로크기
	 * @param int $height 썸네일 세로크기
	 * @param boolean $is_delete 원본 이미지파일을 삭제할 지 여부
	 * @param string $forceType 원본 이미지의 포맷과 무관하게 썸네일의 이미지포맷(JPG, GIF, PNG)를 지정할 경우 해당 포맷명
	 * @return boolean $success
	 */
	function cropThumbnail($imgPath,$thumbPath,$width,$height,$delete=false,$forceType=null) {
		$result = true;
		$imginfo = @getimagesize($imgPath);
		$extName = $imginfo[2];

		if ($imginfo[0] == $width && $imginfo[1] == $height) {
			@copy($imgPath,$thumbPath);
			if ($delete == true) @unlink($imgPath);
			return true;
		}

		switch($extName) {
			case '2' :
				$src = @ImageCreateFromJPEG($imgPath) or $result = false;
				$type = 'jpg';
				break;
			case '1' :
				$src = @ImageCreateFromGIF($imgPath) or $result = false;
				$type = 'gif';
				break;
			case '3' :
				$src = @ImageCreateFromPNG($imgPath) or $result = false;
				$type = 'png';
				break;
			default :
				$result = false;
		}

		if ($result == true) {
			if ($width * $imginfo[1] < $height * $imginfo[0]) {
				$rs_img_width = round($imginfo[1] * ($width / $height));
				$rs_img_height = $imginfo[1];

				$x = round(($imginfo[0] - $rs_img_width) / 2);
				$y = 0;
			} else {
				$rs_img_width  = $imginfo[0];
				$rs_img_height = round($imginfo[0] * ($height / $width));

				$x = 0;
				$y = round(($imginfo[1] - $rs_img_height) / 2);
			}

			$sc_img_width = $rs_img_width;
			$sc_img_height = $rs_img_height;

			$crop = @ImageCreateTrueColor($rs_img_width,$rs_img_height);

			switch ($type) {
				case 'png':
					$background = imagecolorallocate($src,0,0,0);
					imagecolortransparent($crop,$background);
					imagealphablending($crop,false);
					imagesavealpha($crop,true);
					break;

				case 'gif':
					$background = imagecolorallocate($src,0,0,0);
					imagecolortransparent($src,$background);
					break;
			}

			@ImageCopyResampled($crop,$src,0,0,$x,$y,$rs_img_width,$rs_img_height,$rs_img_width,$rs_img_height) or $result = false;

			if ($result == true) {
				$thumb = @ImageCreateTrueColor($width,$height);
				switch ($type) {
					case 'png':
						$background = imagecolorallocate($crop,0,0,0);
						imagecolortransparent($thumb,$background);
						imagealphablending($thumb,false);
						imagesavealpha($thumb,true);
						break;

					case 'gif':
						$background = imagecolorallocate($crop,0,0,0);
						imagecolortransparent($crop,$background);
						break;
				}

				@ImageCopyResampled($thumb,$crop,0,0,0,0,$width,$height,$rs_img_width,$rs_img_height) or $result = false;
			}

			$type = $forceType != null ? $forceType : $type;

			if ($type == 'jpg') {
				@ImageJPEG($thumb,$thumbPath,100) or $result = false;
			} elseif($type == 'gif') {
				@ImageGIF($thumb,$thumbPath,100) or $result = false;
			} elseif($type == 'png') {
				@imagePNG($thumb,$thumbPath) or $result = false;
			} else {
				$result = false;
			}
			@ImageDestroy($src);
			@ImageDestroy($thumb);
			@ImageDestroy($crop);
			@chmod($thumbPath,0755);
		}

		if ($delete == true) {
			@unlink($imgPath);
		}

		return $result;
	}

	/**
	 * 파일 아이콘을 가져온다.
	 *
	 * @param string $type 항목종류
	 * @param string $extension 파일확장자
	 */
	function getFileIcon($type,$extension='') {
		$icon = 'icon_large_etc.png';

		if ($type == 'folder') $icon = 'icon_large_folder.png';
		if ($type == 'document') $icon = 'icon_large_document.png';
		if ($type == 'archive') $icon = 'icon_large_archive.png';
		if ($type == 'video') $icon = 'icon_large_video.png';
		if ($type == 'audio') $icon = 'icon_large_audio.png';
		if ($type == 'image') $icon = 'icon_large_image.png';

		if ($extension == 'hwp') $icon = 'icon_large_hwp.png';
		if ($extension == 'pdf') $icon = 'icon_large_pdf.png';
		if ($extension == 'xls' || $extension == 'xlsx') $icon = 'icon_large_xls.png';
		if ($extension == 'doc' || $extension == 'docx') $icon = 'icon_large_doc.png';
		if ($extension == 'ppt' || $extension == 'pptx') $icon = 'icon_large_ppt.png';

		return $this->getModule()->getDir().'/images/'.$icon;
	}

	/**
	 * 파일정보를 반환한다.
	 *
	 * @param int $idx 파일고유번호
	 * @param boolean $is_realpath 파일의 실제서버상의 경로를 반환할지, 유저가 접근할 수 있는 파일경로(URL)을 반환할 지 여부(기본값 : false)
	 * @param boolean $is_fullurl 파일 URL 의 전체 URL 반환여부(기본값 : false)
	 * @return object $fileInfo 파일정보
	 */
	function getFileInfo($idx,$is_realpath=false,$is_fullurl=false) {
		if (isset($this->files[$idx]) == true && $this->files[$idx]->is_realpath == $is_realpath && $this->files[$idx]->is_fullurl == $is_fullurl) return $this->files[$idx];

		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($file == null) return null;

		$fileInfo = new stdClass();
		$fileInfo->idx = $idx;
		$fileInfo->icon = $this->getFileIcon($file->type,$this->getFileExtension($file->name));
		$fileInfo->name = $file->name;
		$fileInfo->size = $file->origin == 0 ? $file->size : $this->getFileInfo($file->origin)->size;
		$fileInfo->type = $file->type;
		$fileInfo->mime = $file->mime;
		$fileInfo->width = $file->width;
		$fileInfo->height = $file->height;
		$fileInfo->hit = $file->download;
		$fileInfo->path = $is_realpath == true ? $this->getAttachmentPath().'/'.$file->path : $this->getAttachmentUrl($idx,'view',$is_fullurl);
		$fileInfo->thumbnail = $this->getAttachmentUrl($idx,'thumbnail',$is_fullurl);
		$fileInfo->download = $this->getAttachmentUrl($idx,'download',$is_fullurl);
		$fileInfo->reg_date = $file->reg_date;
		$fileInfo->code = Encoder($fileInfo->idx);
		$fileInfo->module = $file->module;
		$fileInfo->target = $file->target;
		$fileInfo->extension = $this->getFileExtension($file->name);
		$fileInfo->status = $file->status;
		$fileInfo->origin = $file->origin;
		$fileInfo->duplicate = $file->duplicate > 0 ? $this->db()->select($this->table->attachment,'idx')->where('origin',$idx)->get('idx') : array();
		$fileInfo->is_realpath = $is_realpath;
		$fileInfo->is_fullurl = $is_fullurl;

		$this->files[$idx] = $fileInfo;

		return $this->files[$idx];
	}

	function getTotalFileSize($files) {
		if (is_array($files) == false || count($files) == 0) return 0;

		$size = $this->db()->select($this->table->attachment,'sum(size) as total_size')->where('idx',$files,'IN')->getOne();
		return isset($size->total_size) == true ? $size->total_size : 0;
	}

	/**
	 * 파일을 삭제한다.
	 *
	 * @param int $idx 파일고유번호
	 * @return boolean $success
	 */
	function fileDelete($idx) {
		if (!$idx) return false;

		$idx = is_array($idx) == false ? array($idx) : $idx;
		if (empty($idx) == true) return false;

		$files = $this->db()->select($this->table->attachment)->where('idx',$idx,'IN')->get();
		foreach ($files as $file) {
			if ($file->module != '' && $file->module != 'site') {
				$mModule = $this->IM->getModule($file->module);

				if (method_exists($mModule,'syncAttachment') == true) {
					$mModule->syncAttachment('delete',$file->idx);
				}
			}

			$this->db()->delete($this->table->attachment)->where('idx',$file->idx)->execute();

			if ($file->origin == 0) {
				if ($file->duplicate == 0) {
					@unlink($this->getAttachmentPath().'/'.$file->path);
					@unlink($this->getAttachmentPath().'/'.$file->path.'.view');
					@unlink($this->getAttachmentPath().'/'.$file->path.'.thumb');
				} else {
					$duplicates = $this->db()->select($this->table->attachment)->where('origin',$file->idx)->orderBy('idx','asc')->get();
					for ($i=0, $loop=count($duplicates);$i<$loop;$i++) {
						if ($i == 0) {
							$this->db()->update($this->table->attachment,array('origin'=>0,'duplicate'=>count($duplicates) - 1,'size'=>$file->size))->where('idx',$duplicates[$i]->idx)->execute();
						} else {
							$this->db()->update($this->table->attachment,array('origin'=>$duplicates[0]->idx))->where('idx',$duplicates[$i]->idx)->execute();
						}
					}
				}
			} else {
				$duplicate = $this->db()->select($this->table->attachment)->where('origin',$file->origin)->count();
				$this->db()->update($this->table->attachment,array('duplicate'=>$duplicate))->where('idx',$file->idx)->execute();
			}
		}

		return true;
	}

	/**
	 * 파일 업로드를 완료한다.
	 * 업로드가 시작되기전 해당파일이 업로드될 임시주소를 먼저 생성하기 위해, 데이터베이스에 업로드예정파일에 대한 메타데이터를 미리 생성하고,
	 * 파일업로드가 완료되는 시점에 해당 파일을 정상업로드 상태로 변경한다.
	 *
	 * @param int $idx 업로드를 완료할 파일고유번호
	 * @return object $fileInfo 업로드가 완료된 파일정보
	 */
	function fileUpload($idx) {
		if (!$idx) return false;

		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		$filePath = $this->getAttachmentPath().'/'.$file->path;

		$insert = array();
		$insert['mime'] = $this->getFileMime($filePath);
		$insert['type'] = $this->getFileType($insert['mime']);
		$hash = md5_file($filePath);
		$insert['path'] = $this->getCurrentPath().'/'.$hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($file->name,$filePath);
		$insert['width'] = 0;
		$insert['height'] = 0;
		if ($insert['type'] == 'image') {
			$check = getimagesize($filePath);
			$insert['width'] = $check[0];
			$insert['height'] = $check[1];
		}

		rename($filePath,$this->getAttachmentPath().'/'.$insert['path']);
		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();

		return $this->getFileInfo($idx);
	}

	/**
	 * 특정경로에 존재하는 파일을 첨부파일모듈상의 파일로 저장한다.
	 *
	 * @param string $name 저장할 파일명
	 * @param string $filePath 저장할 파일이 존재하는 경로
	 * @param string $module 파일을 저장하는 모듈
	 * @param string $target 파일을 저장하는 대상
	 * @param string $status 파일 상태 (DRAFT : 임시파일 / PUBLISHED : 출판된파일)
	 * @param boolean $is_delete 저장할 파일을 첨부파일모듈 폴더구조에 맞게 이동한 뒤, 이동되기전 파일을 삭제할 지 여부(기본값 : true)
	 * @return boolean $success
	 */
	function fileSave($name,$filePath,$module='',$target='',$status='DRAFT',$isDelete=true,$reg_date=null) {
		$reg_date = $reg_date == null ? time() : $reg_date;

		$insert = array();
		$insert['module'] = $module;
		$insert['target'] = $target;
		$insert['name'] = $name;
		$insert['mime'] = $this->getFileMime($filePath);
		$insert['size'] = filesize($filePath);
		$insert['type'] = $this->getFileType($insert['mime']);
		$hash = md5_file($filePath);
		$insert['path'] = $this->getCurrentPath(false,$reg_date).'/'.$hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$filePath);
		$insert['width'] = 0;
		$insert['height'] = 0;
		if ($insert['type'] == 'image') {
			$check = getimagesize($filePath);
			$insert['width'] = $check[0];
			$insert['height'] = $check[1];
		}
		$insert['wysiwyg'] = 'FALSE';
		$insert['reg_date'] = $reg_date;
		$insert['status'] = $status;

		if ($isDelete == true) {
			rename($filePath,$this->getAttachmentPath().'/'.$insert['path']);
		} else {
			copy($filePath,$this->getAttachmentPath().'/'.$insert['path']);
		}

		$idx = $this->db()->insert($this->table->attachment,$insert)->execute();

		return $idx;
	}

	/**
	 * 기존파일을 새로운 파일로 대체한다.
	 *
	 * @param int $idx 대체할 원본파일번호
	 * @param string $name 대체되는 파일명
	 * @param string $filePath 대체되는 파일이 존재하는 경로
	 * @param boolean $is_delete 대체된 기존파일을 삭제할 지 여부(기본값 : true)
	 * @return boolean $success
	 */
	function fileReplace($idx,$name,$filePath,$isDelete=true) {
		if (is_numeric($idx) == false) return false;
		$oFile = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($oFile == null) return false;

		$temp = explode('/',$oFile->path);
		$oName = array_pop($temp);
		$path = implode('/',$temp);

		$insert = array();
		$insert['name'] = $name;
		$insert['mime'] = $this->getFileMime($filePath);
		$insert['size'] = filesize($filePath);
		$insert['type'] = $this->getFileType($insert['mime']);
		$hash = md5_file($filePath);
		$insert['path'] = $path.'/'.$hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$filePath);
		$insert['width'] = 0;
		$insert['height'] = 0;
		if ($insert['type'] == 'image') {
			$check = getimagesize($filePath);
			$insert['width'] = $check[0];
			$insert['height'] = $check[1];
		}
		$insert['origin'] = 0;
		$insert['wysiwyg'] = 'FALSE';

		if ($isDelete == true) {
			rename($filePath,$this->getAttachmentPath().'/'.$insert['path']);
		} else {
			copy($filePath,$this->getAttachmentPath().'/'.$insert['path']);
		}
		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();

		if ($oFile->origin == 0) {
			if ($oFile->duplicate == 0) {
				@unlink($this->getAttachmentPath().'/'.$oFile->path);
				@unlink($this->getAttachmentPath().'/'.$oFile->path.'.view');
				@unlink($this->getAttachmentPath().'/'.$oFile->path.'.thumb');
			} else {
				$duplicates = $this->db()->select($this->table->attachment)->where('origin',$oFile->idx)->orderBy('idx','asc')->get();
				for ($i=0, $loop=count($duplicates);$i<$loop;$i++) {
					if ($i == 0) {
						$this->db()->update($this->table->attachment,array('origin'=>0,'duplicate'=>count($duplicates) - 1,'size'=>$oFile->size))->where('idx',$duplicates[$i]->idx)->execute();
					} else {
						$this->db()->update($this->table->attachment,array('origin'=>$duplicates[0]->idx))->where('idx',$duplicates[$i]->idx)->execute();
					}
				}
			}
		} else {
			$duplicate = $this->db()->select($this->table->attachment)->where('origin',$oFile->origin)->count();
			$this->db()->update($this->table->attachment,array('duplicate'=>$duplicate))->where('idx',$oFile->origin)->execute();
		}

		return $idx;
	}

	/**
	 * 파일을 복사한다.
	 *
	 * @param int $idx 복사할 원본파일번호
	 * @return boolean $success
	 */
	function fileCopy($idx) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($file == null) return false;

		if ($file->origin == 0) {
			unset($file->idx);
			$file->size = 0;
			$file->origin = $idx;
			$file->reg_date = time();
			$file->download = 0;
			$file->status = 'DRAFT';

			$cidx = $this->db()->insert($this->table->attachment,(array)$file)->execute();
			if ($cidx === false) return false;

			$duplicate = $this->db()->select($this->table->attachment)->where('origin',$idx)->count();
			$this->db()->update($this->table->attachment,array('duplicate'=>$duplicate))->where('idx',$idx)->execute();

			return $cidx;
		} else {
			return $this->fileCopy($file->origin);
		}
	}

	/**
	 * 파일을 다운로드한다.
	 *
	 * @param string $idx 다운로드할 파일고유번호
	 * @param boolean $isHit 다운로드 숫자를 증가할지 여부(기본값 : true)
	 */
	function fileDownload($idx,$isHit=true) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();

		/**
		 * 파일을 업로드한 모듈을 호출하여, 파일 다운로드권한을 확인한다.
		 */
		if ($file->module != '' && $file->module != 'site') {
			$mModule = $this->IM->getModule($file->module);

			if (method_exists($mModule,'syncAttachment') == true) {
				$downloadable = $mModule->syncAttachment('download',$idx);
				if ($downloadable !== null && $downloadable !== true) {
					if ($downloadable === false) {
						$this->IM->printError($this->IM->getModule('member')->isLogged() == true ? 'FILE_ACCESS_DENIED' : 'REQUIRED_LOGIN',$this->getAttachmentUrl($idx,'download'));
						exit;
					} else {
						$this->IM->printError($mModule->getErrorText($downloadable,$this->getAttachmentUrl($idx,'download'),true));
						exit;
					}
				}
			}
		}

		if ($file == null) {
			$this->printError('FILE_NOT_FOUND');
			exit;
		} else {
			$filePath = substr($file->path,0,1) == '/' ? $file->path : $this->getAttachmentPath().'/'.$file->path;

			if (is_file($filePath) == true) {
				if ($isHit == true) $this->db()->update($this->table->attachment,array('download'=>$this->db()->inc()))->where('idx',$idx)->execute();
				$file->name = str_replace(' ','_',$file->name);

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false);
				if (preg_match('/Safari/',$_SERVER['HTTP_USER_AGENT']) == true) {
					header('Content-Disposition: attachment; filename="'.$file->name.'"');
				} else {
					header('Content-Disposition: attachment; filename="'.rawurlencode($file->name).'"; filename*=UTF-8\'\''.rawurlencode($file->name));
				}
				header("Content-Transfer-Encoding: binary");
				header('Content-Type: '.($file->mime == 'Unknown' ? 'application/x-unknown' : $file->mime));
				header('Content-Length: '.$file->size);

				session_write_close();

				readfile($filePath);
				exit;
			} else {
				$this->printError('FILE_NOT_FOUND',$filePath);
				exit;
			}
		}
	}

	/**
	 * 임시폴더에 존재하는 임시파일을 다운로드 받는다.
	 *
	 * @param string $name 임시폴더내 존재하는 파일명
	 * @param boolean $is_delete 파일 다운로드 작업 후 해당 임시파일을 삭제할 지 여부 (기본값 false)
	 * @param string $newname 임시파일명을 다운로드 받을 때 다운로드 받아질 파일명
	 */
	function tempFileDownload($name,$is_delete=false,$newname='') {
		if (is_file($this->getTempPath(true).'/'.$name) == true) {
			$mime = $this->getFileMime($this->getTempPath(true).'/'.$name);
			$filename = $this->getSafeFileName($newname ? $newname : $name);

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false);
			if (preg_match('/Safari/',$_SERVER['HTTP_USER_AGENT']) == true) {
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			} else {
				header('Content-Disposition: attachment; filename="'.rawurlencode($filename).'"; filename*=UTF-8\'\''.rawurlencode($filename));
			}
			header("Content-Transfer-Encoding: binary");
			header('Content-Type: '.$mime);
			header('Content-Length: '.filesize($this->getTempPath(true).'/'.$name));

			session_write_close();

			readfile($this->getTempPath(true).'/'.$name);

			if ($is_delete == true) {
				flush();
				sleep(1);
				unlink($this->getTempPath(true).'/'.$name);
			}
			exit;
		} else {
			$this->printError('FILE_NOT_FOUND',$this->getTempPath(true).'/'.$name);
			exit;
		}
	}

	/**
	 * 파일정보를 출판됨 상태로 변경한다.
	 * 기본적으로 업로드된 파일은 임시파일상태로 업로드가 되며, 출판상태로 변경되지 않을 경우 임시파일정리 작업시 파일이 삭제된다.
	 *
	 * @param int $idx 파일고유번호
	 * @param string $module 파일을 출판한 모듈명(없을 경우 파일업로드시 기록된 모듈명을 유지한다.)
	 * @param string $target 파일을 출판한 대상(없을 경우 파일업로드시 기록된 대상을 유지한다.)
	 * @param string $name 파일명(없을 경우 파일업로드시 기록된 대상을 유지한다.)
	 * @return boolean $success
	 */
	function filePublish($idx,$module=null,$target=null,$name=null) {
		if (!$idx) return false;

		if (isset($this->files[$idx]) == true) unset($this->files[$idx]);

		$insert = array('status'=>'PUBLISHED');
		if ($module != null) $insert['module'] = $module;
		if ($target != null) $insert['target'] = $target;
		if ($name != null) $insert['name'] = $name;

		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();
		return true;
	}

	/**
	 * 파일접근과 관련된 에러메세지를 띄운다.
	 *
	 * @param string $code 에러코드
	 * @param string $path 파일경로 또는 파일명
	 */
	function printError($code,$path=null) {
		$error = new stdClass();
		$error->message = $this->getErrorText($code);
		$error->description = $path;
		$error->type = 'back';

		if ($code == 'FILE_NOT_FOUND') {
			header("HTTP/1.1 404 Not Found");
		}

		if ($code == 'FILE_ACCESS_DENIED') {
			header("HTTP/1.1 403 FORBIDDEN");
		}

		$this->IM->printError($error);
	}

	/**
	 * 현재 모듈에서 처리해야하는 요청이 들어왔을 경우 처리하여 결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 * 작업코드가 '@' 로 시작할 경우 사이트관리자를 위한 작업으로 최고관리자 권한이 필요하다.
	 *
	 * @param string $action 작업코드
	 * @return object $results 수행결과
	 * @see /process/index.php
	 */
	function doProcess($action) {
		$results = new stdClass();

		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeDoProcess',$this->getModule()->getName(),$action,$values);

		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/process/'.$action.'.php';
		}

		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterDoProcess',$this->getModule()->getName(),$action,$values,$results);

		return $results;
	}
}
?>