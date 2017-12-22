<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 아이모듈 코어 및 모든 모듈에서 첨부파일과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/attachment/ModuleAttachment.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 22.
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
	
	private $_buffers = array();
	
	/**
	 * 첨부파일 설정변수
	 */
	private $_id = null;
	private $_name = null;
	private $_templet = '#';
	private $_templet_file = null;
	private $_module = null;
	private $_target = null;
	private $_wysiwyg = false;
	private $_wysiwygOnly = false;
	private $_buttonText = null;
	private $_loader = null;
	
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
			case 'NOT_ALLOWED_SIGNUP' :
				if ($value != null && is_object($value) == true) {
					$description = $value->title;
				}
				break;
				
			case 'DISABLED_LOGIN' :
				if ($value != null && is_numeric($value) == true) {
					$description = str_replace('{SECOND}',$value,$this->getText('text/remain_time_second'));
				}
				break;
			
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
		$this->_name = null;
		$this->_templet = '#';
		$this->_templet_file = null;
		$this->_module = null;
		$this->_target = null;
		$this->_wysiwyg = false;
		$this->_buttonText = null;
		$this->_loader = null;
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
	 */
	function get() {
		$this->preload();
		
		$this->_id = $this->_id == null ? uniqid('UPLOADER_') : $this->_id;
		
		$header = PHP_EOL.'<!-- ATTACHMENT MODULE -->'.PHP_EOL;
		$header.= '<div id="'.$this->_id.'" data-role="module" data-module="attachment" data-templet="'.$this->getTemplet($this->_templet)->getName().'" data-uploader="TRUE"';
		if ($this->_module != null) $header.= ' data-uploader-module="'.$this->_module.'"';
		if ($this->_target != null) $header.= ' data-uploader-target="'.$this->_target.'"';
		if ($this->_loader != null) $header.= ' data-uploader-loader="'.$this->_loader.'"';
		$header.= ' data-uploader-wysiwyg="'.($this->_wysiwyg == true ? 'TRUE' : 'FALSE').'"';
		$header.= '>'.PHP_EOL;
		$header.= '<div style="display:none;"><input type="file" multiple></div>'.PHP_EOL;
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
	
	function getCurrentPath($isFullPath=false) {
		$folder = date('Ym');
		if (is_dir($this->IM->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->IM->getAttachmentPath().'/'.$folder);
			chmod($this->IM->getAttachmentPath().'/'.$folder,0707);
		}
		
		if ($isFullPath == true) $folder = $this->IM->getAttachmentPath().'/'.$folder;
		return $folder;
	}
	
	function getTempDir($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->IM->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->IM->getAttachmentPath().'/'.$folder);
			chmod($this->IM->getAttachmentPath().'/'.$folder,0707);
		}
		
		if ($isFullPath == true) $folder = $this->IM->getAttachmentDir().'/'.$folder;
		return $folder;
	}
	
	function getTempPath($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->IM->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->IM->getAttachmentPath().'/'.$folder);
			chmod($this->IM->getAttachmentPath().'/'.$folder,0707);
		}
		
		if ($isFullPath == true) $folder = $this->IM->getAttachmentPath().'/'.$folder;
		return $folder;
	}
	
	function getTempFile($isFullPath=false) {
		while (true) {
			$hash = md5(time().rand(10000000,99999999));
			if (is_file($this->getTempPath(true).'/'.$hash) == false) break;
		}
		
		return $this->getTempPath($isFullPath).'/'.$hash;
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
	
	function getFileExtension($filename,$filepath='') {
		return strtolower(pathinfo($filename,PATHINFO_EXTENSION));
	}
	
	function getPreviewHtml($filename,$filepath) {
		
	}
	
	function getAttachmentUrl($idx,$view='view',$isFullUrl=false) {
		if (is_object($idx) == true) {
			$file = $idx;
		} else {
			$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		}
		
		if ($isFullUrl == true) {
			$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
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
				elseif (file_exists($this->IM->getAttachmentPath().'/'.$file->path.'.thumb') == true) return $url.'/attachment/thumbnail/'.$file->idx.'/'.urlencode($file->name).'.jpg';
				else return null;
			}
			
			return $url.'/attachment/'.$view.'/'.$file->idx.'/'.urlencode($file->name);
		}
	}
	
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
			// Change FileName
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
			
			// copyresampled 값이 동일하다 why? 이미지의 확대 축소가 발생하지는 않기 때문이다. 
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
			// Change FileName
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
	
	function getFileInfo($idx,$is_realpath=false) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($file == null) return null;
		
		$fileInfo = new stdClass();
		$fileInfo->idx = $idx;
		$fileInfo->icon = $this->getFileIcon($file->type,$this->getFileExtension($file->name));
		$fileInfo->name = $file->name;
		$fileInfo->size = $file->size;
		$fileInfo->type = $file->type;
		$fileInfo->mime = $file->mime;
		$fileInfo->width = $file->width;
		$fileInfo->height = $file->height;
		$fileInfo->hit = $file->download;
		$fileInfo->path = $is_realpath == true ? $this->IM->getAttachmentPath().'/'.$file->path : $this->getAttachmentUrl($idx);
		$fileInfo->thumbnail = $this->getAttachmentUrl($idx,'thumbnail');
		$fileInfo->download = $this->getAttachmentUrl($idx,'download');
		$fileInfo->reg_date = $file->reg_date;
		$fileInfo->code = Encoder($fileInfo->idx);
		$fileInfo->module = $file->module;
		$fileInfo->target = $file->target;
		$fileInfo->extension = $this->getFileExtension($file->name);
		$fileInfo->status = $file->status;
		
		return $fileInfo;
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
		for ($i=0, $loop=count($files);$i<$loop;$i++) {
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path);
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path.'.view');
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path.'.thumb');
			
			if ($files[$i]->module != '' && $files[$i]->module != 'site') {
				$mModule = $this->IM->getModule($files[$i]->module);
				
				if (method_exists($mModule,'syncAttachment') == true) {
					$mModule->syncAttachment('delete',$files[$i]->idx);
				}
			}
			
			$this->db()->delete($this->table->attachment)->where('idx',$files[$i]->idx)->execute();
		}
		
		return true;
	}
	
	function fileUpload($idx) {
		if (!$idx) return false;
		
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		$filePath = $this->IM->getAttachmentPath().'/'.$file->path;
		
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

		rename($filePath,$this->IM->getAttachmentPath().'/'.$insert['path']);
		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();
		
		return $this->getFileInfo($idx);
	}
	
	function fileSave($name,$filePath,$module='',$target='',$status='DRAFT',$isDelete=true) {
		$insert = array();
		$insert['module'] = $module;
		$insert['target'] = $target;
		$insert['name'] = $name;
		$insert['mime'] = $this->getFileMime($filePath);
		$insert['size'] = filesize($filePath);
		$insert['type'] = $this->getFileType($insert['mime']);
		$hash = md5_file($filePath);
		$insert['path'] = $this->getCurrentPath().'/'.$hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$filePath);
		$insert['width'] = 0;
		$insert['height'] = 0;
		if ($insert['type'] == 'image') {
			$check = getimagesize($filePath);
			$insert['width'] = $check[0];
			$insert['height'] = $check[1];
		}
		$insert['wysiwyg'] = 'FALSE';
		$insert['reg_date'] = time();
		$insert['status'] = $status;

		if ($isDelete == true) {
			rename($filePath,$this->IM->getAttachmentPath().'/'.$insert['path']);
		} else {
			copy($filePath,$this->IM->getAttachmentPath().'/'.$insert['path']);
		}
		
		$idx = $this->db()->insert($this->table->attachment,$insert)->execute();
		
		return $idx;
	}
	
	function fileReplace($idx,$name,$filePath,$isDelete=true) {
		if (is_numeric($idx) == false) return false;
		$oFile = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($oFile == null) return false;
		
		if ($oFile != null) {
			@unlink($this->IM->getAttachmentPath().'/'.$oFile->path);
			@unlink($this->IM->getAttachmentPath().'/'.$oFile->path.'.thumb');
			@unlink($this->IM->getAttachmentPath().'/'.$oFile->path.'.view');
		}
		
		$insert = array();
		$insert['name'] = $name;
		$insert['mime'] = $this->getFileMime($filePath);
		$insert['size'] = filesize($filePath);
		$insert['type'] = $this->getFileType($insert['mime']);
		$hash = md5_file($filePath);
		$insert['path'] = $this->getCurrentPath().'/'.$hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$filePath);
		$insert['width'] = 0;
		$insert['height'] = 0;
		if ($insert['type'] == 'image') {
			$check = getimagesize($filePath);
			$insert['width'] = $check[0];
			$insert['height'] = $check[1];
		}
		$insert['wysiwyg'] = 'FALSE';
		$insert['reg_date'] = time();

		if ($isDelete == true) {
			rename($filePath,$this->IM->getAttachmentPath().'/'.$insert['path']);
		} else {
			copy($filePath,$this->IM->getAttachmentPath().'/'.$insert['path']);
		}
		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();
		
		return $idx;
	}
	
	function fileCopy($idx,$target=null) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($file == null) return false;
		
		if ($target == null) {
			return $this->fileSave($file->name,$this->IM->getAttachmentPath().'/'.$file->path,$file->module,$file->target,$file->status,false);
		} else {
			return $this->fileReplace($target,$file->name,$this->IM->getAttachmentPath().'/'.$file->path,false);
		}
	}
	
	function fileDownload($idx,$isHit=true) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		$downloadable = true;
		
		if ($file->module != '' && $file->module != 'site') {
			$mModule = $this->IM->getModule($file->module);
			
			if (method_exists($mModule,'syncAttachment') == true) {
				$downloadable = $mModule->syncAttachment('download',$idx) !== false;
			}
		}
		
		if ($file == null) {
			header("HTTP/1.1 404 Not Found");
			$this->IM->printError('FILE_NOT_FOUND',null,null,true);
			exit;
		} elseif ($downloadable === false) {
			header("HTTP/1.1 403 Not Found");
			$this->IM->printError('FORBIDDEN',null,null,true);
			exit;
		} else {
			$filePath = substr($file->path,0,1) == '/' ? $file->path : $this->IM->getAttachmentPath().'/'.$file->path;
			
			if (is_file($filePath) == true) {
				if ($isHit == true) $this->db()->update($this->table->attachment,array('download'=>$this->db()->inc()))->where('idx',$idx)->execute();
				$file->name = str_replace(' ','_',$file->name);
	
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
				header("Cache-Control: private",false);
				header('Content-Disposition: attachment; filename="'.rawurlencode($file->name).'"; filename*=UTF-8\'\''.rawurlencode($file->name));
				header("Content-Transfer-Encoding: binary");
				header('Content-Type: '.($file->mime == 'Unknown' ? 'application/x-unknown' : $file->mime));
				header('Content-Length: '.$file->size);
				
				readfile($filePath);
				exit;
			} else {
				header("HTTP/1.1 404 Not Found");
				$this->IM->printError('FILE_NOT_FOUND');
				exit;
			}
		}
	}
	
	function tempFileDownload($name,$is_delete=false,$newname='') {
		if (file_exists($this->getTempPath(true).'/'.$name) == true) {
			$mime = $this->getFileMime($this->getTempPath(true).'/'.$name);
			$filename = $newname ? $newname : $name;
			
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
			header("Cache-Control: private",false);
			header('Content-Disposition: attachment; filename="'.rawurlencode($filename).'"; filename*=UTF-8\'\''.rawurlencode($filename));
			header("Content-Transfer-Encoding: binary");
			header('Content-Type: '.$mime);
			header('Content-Length: '.filesize($this->getTempPath(true).'/'.$name));

			readfile($this->getTempPath(true).'/'.$name);
			
//			if ($is_delete == true) unlink($this->getTempPath(true).'/'.$name);
			exit;
		}
	}
	
	function filePublish($idx,$module=null,$target=null) {
		if (!$idx) return false;
		
		$insert = array('status'=>'PUBLISHED');
		if ($module != null) $insert['module'] = $module;
		if ($target != null) $insert['target'] = $target;
		
		$this->db()->update($this->table->attachment,$insert)->where('idx',$idx)->execute();
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