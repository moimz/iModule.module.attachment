<?php
/**
 * This file is part of iModule - https://www.imodule.kr
 *
 * @file ModuleAttachment.class.php
 * @author Arzz
 * @license MIT License
 */
class ModuleAttachment {
	private $IM; // Linked iModule core
	private $Module; // Linked Module core
	
	private $lang = null; // Store language strings
	private $oLang = null; // Store original language(defined package.json) strings
	private $table; // defined modules' database tables
	
	private $_buffers = array();
	
	private $_id = null;
	private $_name = null;
	private $_templet = 'default';
	private $_module = null;
	private $_target = null;
	private $_wysiwyg = false;
	private $_wysiwygOnly = false;
	private $_loadFile = array();
	
	function __construct($IM,$Module) {
		$this->IM = $IM;
		$this->Module = $Module;
		
		$this->table = new stdClass();
		$this->table->attachment = 'attachment_table';
	}
	
	/**
	 * Get database for this Module
	 *
	 * @return object DB.class
	 */
	function db() {
		return $this->IM->db('default');//$this->Module->getInstalled()->database);
	}
	
	/**
	 * Get Database table from the others class (table is private value)
	 *
	 * @param string $table table code
	 * @return string $tableName return real table name without prefix
	 */
	function getTable($table) {
		return $this->table->$table;
	}
	
	/**
	 * Get API /$language/api/board/$api
	 *
	 * @param string $api API code
	 * @return object $data return data for API code
	 */
	function getApi($api) {
		$data = new stdClass();
		
		return $data;
	}
	
	/**
	 * Get language string from language code
	 *
	 * @param string $code language code (json key)
	 * @return string language string
	 */
	function getLanguage($code) {
		if ($this->lang == null) {
			if (file_exists($this->Module->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->Module->getPackage()->language) {
					$this->oLang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->Module->getPackage()->language.'.json'));
				}
			} else {
				$this->lang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->Module->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$temp = explode('/',$code);
		if (count($temp) == 1) {
			return isset($this->lang->$code) == true ? $this->lang->$code : ($this->oLang != null && isset($this->oLang->$code) == true ? $this->oLang->$code : '');
		} else {
			$string = $this->lang;
			for ($i=0, $loop=count($temp);$i<$loop;$i++) {
				if (isset($string->{$temp[$i]}) == true) $string = $string->{$temp[$i]};
				else $string = null;
			}
			
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) $string = $string->{$temp[$i]};
					else $string = null;
				}
			}
			return $string == null ? '' : $string;
		}
	}
	
	function reset() {
		$this->id = null;
		$this->_name = null;
		$this->_templet = 'default';
		$this->_module = null;
		$this->_target = null;
		$this->_wysiwyg = false;
		$this->_wysiwygOnly = false;
		$this->_loadFile = array();
	}
	
	function setId($id) {
		$this->_id = $id;
		
		return $this;
	}
	
	function setTemplet($templet) {
		$this->_templet = $templet;
		
		return $this;
	}
	
	function setModule($module) {
		$this->_module = $module;
		
		return $this;
	}
	
	function setTarget($target) {
		$this->_target = $target;
		
		return $this;
	}
	
	function setWysiwyg($wysiwyg) {
		$this->_target = $wysiwyg;
		$this->_wysiwyg = true;
		
		return $this;
	}
	
	function setWysiwygOnly($wysiwyg) {
		$this->_wysiwygOnly = true;
		
		return $this;
	}
	
	function isWysiwyg() {
		return $this->_wysiwyg;
	}
	
	function isWysiwygOnly() {
		return $this->_wysiwygOnly;
	}
	
	function loadFile($fileIDX=array()) {
		$this->_loadFile = $fileIDX;
		
		return $this;
	}
	
	function preload($includeTemplet=true) {
		$this->IM->addSiteHeader('script',$this->Module->getDir().'/scripts/attachment.js');
		
		if ($includeTemplet === true) {
			$templetPath = $this->Module->getPath().'/templets/'.$this->_templet;
			$templetDir = $this->Module->getDir().'/templets/'.$this->_templet;
			
			if (file_exists($templetPath.'/scripts/script.js') == true) {
				$this->IM->addSiteHeader('script',$templetDir.'/scripts/script.js');
			}
			
			if (file_exists($templetPath.'/styles/style.css') == true) {
				$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
			}
		}
	}
	
	function doLayout() {
		$this->IM->addSiteHeader('script',$this->Module->getDir().'/scripts/attachment.js');
		
		ob_start();
		
		$this->_id = $this->_id == null ? uniqid('UPLOADER_') : $this->_id;
		
		if (preg_match('/\.php$/',$this->_templet) == true) {
			$temp = explode('/',$this->_templet);
			$templetFile = array_pop($temp);
			$this->_templet = end($temp);
			$templetPath = implode('/',$temp);
			$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
		} elseif (preg_match('/^@/',$this->_templet) == true) {
			$templetFile = 'templet.php';
			$templetPath = __IM_PATH__.'/templets/'.$this->IM->getSite()->templet.'/modules/attachment/templets/'.preg_replace('/^@/','',$this->_templet);
			$templetDir = __IM_DIR__.'/templets/'.$this->IM->getSite()->templet.'/modules/attachment/templets/'.preg_replace('/^@/','',$this->_templet);
		} else {
			$templetFile = 'templet.php';
			$templetPath = $this->Module->getPath().'/templets/'.$this->_templet;
			$templetDir = $this->Module->getDir().'/templets/'.$this->_templet;
		}
		
		$inputForm = '<div style="display:none;"><input type="file" name="attachment_file" data-attachment-input-file="true" multiple><input type="file" name="attachment_image" data-attachment-input-file="true" multiple><input type="file" name="wysiwyg_image" accept="image/*" data-attachment-input-file="true" multiple><input type="file" name="wysiwyg_file" data-attachment-input-file="true" multiple></div>';
		
		$html = '<div id="'.$this->_id.'" data-templet="'.$this->_templet.'">'.PHP_EOL;
		
		$IM = $this->IM;
		$Module = $this;
		$id = $this->_id;
		
		if (file_exists($templetPath.'/scripts/script.js') == true) {
			$this->IM->addSiteHeader('script',$templetDir.'/scripts/script.js');
		}
		
		if (file_exists($templetPath.'/styles/style.css') == true) {
			$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
		}
		
		if (file_exists($templetPath.'/'.$templetFile) == true) {
			INCLUDE $templetPath.'/'.$templetFile;
		}
		
		$html.= ob_get_contents();
		ob_end_clean();
		
		$html.= $this->_buildScript();
		$html.= '</div>'.PHP_EOL;
		
		$this->IM->fireEvent('afterDoLayout','attachment','doLayout',null,null,$html);
		
		$this->reset();
		echo $html;
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
	
	function getTempPath($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->IM->getAttachmentPath().'/'.$folder) == false) {
			mkdir($this->IM->getAttachmentPath().'/'.$folder);
			chmod($this->IM->getAttachmentPath().'/'.$folder,0707);
		}
		
		if ($isFullPath == true) $folder = $this->IM->getAttachmentPath().'/'.$folder;
		return $folder;
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
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo,$path);
		finfo_close($finfo);
		
		return $mime;
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
				@ImageJPEG($thumb,$thumbPath,75) or $result = false;
			} elseif($type == 'gif') {
				@ImageGIF($thumb,$thumbPath,75) or $result = false;
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
				@ImageJPEG($thumb,$thumbPath,75) or $result = false;
			} elseif($type == 'gif') {
				@ImageGIF($thumb,$thumbPath,75) or $result = false;
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
	
	function getFileInfo($idx) {
		$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
		if ($file == null) return null;
		
		$fileInfo = new stdClass();
		$fileInfo->idx = $idx;
		$fileInfo->name = $file->name;
		$fileInfo->size = $file->size;
		$fileInfo->type = $file->type;
		$fileInfo->mime = $file->mime;
		$fileInfo->width = $file->width;
		$fileInfo->height = $file->height;
		$fileInfo->hit = $file->download;
		$fileInfo->path = $this->getAttachmentUrl($idx);
		$fileInfo->thumbnail = $this->getAttachmentUrl($idx,'thumbnail');
		$fileInfo->download = $this->getAttachmentUrl($idx,'download');
		$fileInfo->reg_date = $file->reg_date;
		$fileInfo->code = Encoder($fileInfo->idx);
		
		return $fileInfo;
	}
	
	function fileDelete($idx) {
		$idx = is_array($idx) == false ? array($idx) : $idx;
		if (empty($idx) == true) return;
		
		$files = $this->db()->select($this->table->attachment)->where('idx',$idx,'IN')->get();
		for ($i=0, $loop=count($files);$i<$loop;$i++) {
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path);
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path.'.view');
			@unlink($this->IM->getAttachmentPath().'/'.$files[$i]->path.'.thumb');
			
			if ($files[$i]->module != '' && $files[$i]->module != 'site') $this->IM->getModule($files[$i]->module)->deleteAttachment($files[$i]->idx);
			$this->db()->delete($this->table->attachment)->where('idx',$files[$i]->idx)->execute();
		}
	}
	
	function fileUpload($idx) {
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
		
		if ($file == null) {
			header("HTTP/1.1 404 Not Found");
			$this->IM->printError('FILE_NOT_FOUND');
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
				header('Content-Disposition: attachment; filename="'.$file->name.'"; filename*=UTF-8\'\''.rawurlencode($file->name));
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
			header('Content-Disposition: attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.rawurlencode($filename));
			header("Content-Transfer-Encoding: binary");
			header('Content-Type: '.$mime);
			header('Content-Length: '.filesize($this->getTempPath(true).'/'.$name));

			readfile($this->getTempPath(true).'/'.$name);
			exit;
		}
	}
	
	function filePublish($idx) {
		$this->db()->update($this->table->attachment,array('status'=>'PUBLISHED'))->where('idx',$idx)->execute();
	}
	
	function doProcess($action) {
		$results = new stdClass();
		$values = new stdClass();
		
		if ($action == 'view') {
			$idx = Request('idx');
			$name = Request('name');
			
			$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
			if ($file == null) {
				header("HTTP/1.1 404 Not Found");
				exit;
			} else {
				if (in_array($file->type,array('image','video')) == true && file_exists($this->IM->getAttachmentPath().'/'.$file->path) == true) {
					header('Content-Type: '.$file->mime);
					
					if ($file->width > 1000) {
						if (file_exists($this->IM->getAttachmentPath().'/'.$file->path.'.view') == true) {
							if ($file->type == 'image') header('Content-Type: '.$file->mime);
							else header('Content-Type: image/jpeg');
							header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.view'));
							readfile($this->IM->getAttachmentPath().'/'.$file->path.'.view');
							exit;
						} elseif ($file->type == 'image' && file_exists($this->IM->getAttachmentPath().'/'.$file->path) == true) {
							if ($this->createThumbnail($this->IM->getAttachmentPath().'/'.$file->path,$this->IM->getAttachmentPath().'/'.$file->path.'.view',1000,0,false) == false) {
								header("HTTP/1.1 404 Not Found");
								exit;
							}
							header('Content-Type: '.$file->mime);
							header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.view'));
							readfile($this->IM->getAttachmentPath().'/'.$file->path.'.view');
							exit;
						} else {
							header("HTTP/1.1 404 Not Found");
							exit;
						}
					} else {
						header('Content-Type: '.$file->size);
						readfile($this->IM->getAttachmentPath().'/'.$file->path);
					}
					exit;
				} elseif (in_array($file->type,array('icon','svg')) == true) {
					if (file_exists($this->IM->getAttachmentPath().'/'.$file->path) == true) {
						header('Content-Type: '.$file->mime);
						header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path));
						readfile($this->IM->getAttachmentPath().'/'.$file->path);
						exit;
					} else {
						header("HTTP/1.1 404 Not Found");
						exit;
					}
				} else {
					header("HTTP/1.1 404 Not Found");
					exit;
				}
			}
		}
		
		if ($action == 'thumbnail') {
			$idx = Request('idx');
			$name = Request('name');
			
			$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
			
			if ($file == null) {
				header("HTTP/1.1 404 Not Found");
				exit;
			} else {
				if (file_exists($this->IM->getAttachmentPath().'/'.$file->path.'.thumb') == true) {
					if ($file->type == 'image') header('Content-Type: '.$file->mime);
					else header('Content-Type: image/jpeg');
					header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.thumb'));
					readfile($this->IM->getAttachmentPath().'/'.$file->path.'.thumb');
					exit;
				} elseif ($file->type == 'image' && file_exists($this->IM->getAttachmentPath().'/'.$file->path) == true) {
					if ($this->createThumbnail($this->IM->getAttachmentPath().'/'.$file->path,$this->IM->getAttachmentPath().'/'.$file->path.'.thumb',($file->width <= $file->height ? 300 : 0),($file->width > $file->height ? 300 : 0),false) == false) {
						header("HTTP/1.1 404 Not Found");
						exit;
					}
					header('Content-Type: '.$file->mime);
					header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.thumb'));
					readfile($this->IM->getAttachmentPath().'/'.$file->path.'.thumb');
					exit;
				} else {
					header("HTTP/1.1 404 Not Found");
					exit;
				}
			}
		}
		
		if ($action == 'download') {
			$idx = Request('idx');
			$name = Request('name');
			
			$this->fileDownload($idx);
		}
		
		if ($action == 'load') {
			$idx = Decoder(Request('key')) != false ? json_decode(Decoder(Request('key'))) : array();
			$values->files = array();
			for ($i=0, $loop=sizeof($idx);$i<$loop;$i++) {
				$fileInfo = $this->getFileInfo($idx[$i]);
				if ($fileInfo != null) $values->files[] = $fileInfo;
			}
			$results->success = true;
			$results->files = $values->files;
		}
		
		if ($action == 'upload') {
			$idx = Request('idx');
			if ($idx == null) {
				$values->status = 'METADATA';
				$meta = json_decode(Request('meta'));
				
				if ($meta != null) {
					$mNormalizer = new UnicodeNormalizer();
					$meta->name = $mNormalizer->normalize($meta->name);
					$path = $this->getTempPath().'/'.md5(Request('meta')).'.'.base_convert(microtime(true)*10000,10,32).'.temp';
					$idx = $this->db()->insert($this->table->attachment,array('module'=>$meta->module,'target'=>$meta->target,'path'=>$path,'name'=>$meta->name,'type'=>$this->getFileType($meta->type),'mime'=>$meta->type,'size'=>$meta->size,'wysiwyg'=>$meta->wysiwyg == true ? 'TRUE' : 'FALSE','reg_date'=>time()))->execute();
					
					$values->fileInfo = $this->getFileInfo($idx);
					$results->success = true;
					$results->idx = $idx;
					$results->code = Encoder($idx);
				} else {
					$results->success = false;
					$results->message = 'METADATA ERROR';
				}
			} else {
				$idx = Decoder(Request('idx'));
				if ($idx) {
					$fileInfo = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
					if ($fileInfo != null) {
						if (isset($_SERVER['HTTP_CONTENT_RANGE']) == true && preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/',$_SERVER['HTTP_CONTENT_RANGE'],$fileRange) == true) {
							$values->chunkBytes = file_get_contents("php://input");
							$values->chunkRangeStart = intval($fileRange[1]);
							$values->chunkRangeEnd = intval($fileRange[2]);
							$values->chunkTotalLength = intval($fileRange[3]);
							
							if ($values->chunkRangeStart === 0) {
								$fp = fopen($this->IM->getAttachmentPath().'/'.$fileInfo->path,'w');
							} else {
								$fp = fopen($this->IM->getAttachmentPath().'/'.$fileInfo->path,'a');
							}
							fseek($fp,$values->chunkRangeStart);
							fwrite($fp,$values->chunkBytes);
							fclose($fp);
							
							if ($values->chunkRangeEnd + 1 === $values->chunkTotalLength) {
								if (intval($fileInfo->size) != filesize($this->IM->getAttachmentPath().'/'.$fileInfo->path)) {
									unlink($this->IM->getAttachmentPath().'/'.$fileInfo->path);
									$this->db()->delete($this->table->attachment)->where('idx',$fileInfo->idx)->execute();
									$results->success = false;
									$results->message = 'SIZE NOT MATCHED ('.strlen($values->chunkBytes).'/'.$fileInfo->size.'/'.filesize($this->IM->getAttachmentPath().'/'.$fileInfo->path).')';
								} else {
									$values->status = 'COMPLETE';
									$values->fileInfo = $this->fileUpload($fileInfo->idx);
									$results->success = true;
									$results->file = $values->fileInfo;
								}
							} else {
								$values->status = 'UPLOADING';
								$values->fileInfo = $fileInfo;
								$results->success = true;
							}
						} else {
							$results->success = false;
							$results->message = 'HEADER ERROR';
						}
					} else {
						$results->success = false;
						$results->message = 'UNREGISTED FILE';
					}
				} else {
					$results->success = false;
					$results->message = 'NOT FOUND IDX';
				}
			}
		}
		
		if ($action == 'wysiwyg_upload') {
			if (isset($_FILES['file']['name']) == true && $_FILES['file']['name']) {
				$_module = Request('_module');
				$_target = Request('_target');
				
				$name = $_FILES['file']['name'];
				$mime = $this->getFileMime($_FILES['file']['tmp_name']);
				$type = $this->getFileType($mime);
				if ($type == 'image') {
					if ($name == 'blob') {
						$extension = explode('/',$mime);
						$name = 'clipboard.'.end($extension);
					}
					$check = getimagesize($_FILES['file']['tmp_name']);
					$width = $check[0];
					$height = $check[1];
				} else {
					$width = $height = 0;
				}
				$size = filesize($_FILES['file']['tmp_name']);
				$path = $this->getCurrentPath().'/'.md5_file($_FILES['file']['tmp_name']).'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$_FILES['file']['tmp_name']);
				move_uploaded_file($_FILES['file']['tmp_name'],$this->IM->getAttachmentPath().'/'.$path);
				
				$idx = $this->db()->insert($this->table->attachment,array('module'=>$_module,'target'=>$_target,'path'=>$path,'name'=>$name,'type'=>$type,'mime'=>$mime,'size'=>$size,'width'=>$width,'height'=>$height,'wysiwyg'=>'TRUE','reg_date'=>time()))->execute();
				
				$results->success = true;
				$results->fileInfo = $this->getFileInfo($idx);
				$results->link = $results->fileInfo->path;
			}
		}
		
		if ($action == '@updateFileType') {
			$attachments = $this->db()->select($this->table->attachment)->get();
			for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
				$type = $this->getFileType($attachments[$i]->mime);
				$this->db()->update($this->table->attachment,array('type'=>$type))->where('idx',$attachments[$i]->idx)->execute();
			}
			
			$results->success = true;
		}
		
		if ($action == '@updateStatus') {
			$updates = array();
			$files = $this->db()->select($this->table->attachment)->get();
			
			for ($i=0, $loop=count($files);$i<$loop;$i++) {
				if ($files[$i]->module == 'site') {
					if ($files[$i]->target == 'logo') {
						$sites = $this->IM->db()->select($this->IM->getTable('site'))->get();
						$isPublished = false;
						foreach ($sites as $domain=>$site) {
							$logo = json_decode($site->logo,true);
							if (in_array($files[$i]->idx,$logo) == true) {
								$isPublished = true;
								break;
							}
						}
						
						if ($isPublished == true) {
							if ($files[$i]->status == 'DRAFT') {
								$this->db()->update($this->table->attachment,array('status'=>'PUBLISHED'))->where('idx',$files[$i]->idx)->execute();
								$files[$i]->status = 'PUBLISHED';
								array_push($updates,$files[$i]);
							}
						} else {
							if ($files[$i]->status == 'PUBLISHED') {
								$this->db()->update($this->table->attachment,array('status'=>'DRAFT'))->where('idx',$files[$i]->idx)->execute();
								$files[$i]->status = 'DRAFT';
								array_push($updates,$files[$i]);
							}
						}
					} else {
						$attachment = $this->IM->db()->select($this->IM->getTable('site'))->where($files[$i]->target,$files[$i]->idx)->getOne();
						if ($files[$i]->target == 'image' && $attachment == null) $attachment = $this->IM->db()->select($this->IM->getTable('page'))->where('image',$files[$i]->idx)->getOne();
						if ($attachment == null) {
							if ($files[$i]->status == 'PUBLISHED') {
								$this->db()->update($this->table->attachment,array('status'=>'DRAFT'))->where('idx',$files[$i]->idx)->execute();
								$files[$i]->status = 'DRAFT';
								array_push($updates,$files[$i]);
							}
						} else {
							if ($files[$i]->status == 'DRAFT') {
								$this->db()->update($this->table->attachment,array('status'=>'PUBLISHED'))->where('idx',$files[$i]->idx)->execute();
								$files[$i]->status = 'PUBLISHED';
								array_push($updates,$files[$i]);
							}
						}
					}
				} else {
					if ($this->Module->isInstalled($files[$i]->module) == true) { // Installed module
						$module = $this->IM->getModule($files[$i]->module);
						if (method_exists($module,'getAttachmentFile') == true) {
							$attachment = $module->getAttachmentFile($files[$i]);
							if ($attachment == null) {
								if ($files[$i]->status == 'PUBLISHED') {
									$this->db()->update($this->table->attachment,array('status'=>'DRAFT'))->where('idx',$files[$i]->idx)->execute();
									$files[$i]->status = 'DRAFT';
									array_push($updates,$files[$i]);
								}
							} else {
								if ($files[$i]->status == 'DRAFT') {
									$this->db()->update($this->table->attachment,array('status'=>'PUBLISHED'))->where('idx',$files[$i]->idx)->execute();
									$files[$i]->status = 'PUBLISHED';
									array_push($updates,$files[$i]);
								}
							}
						}
					} else { // Not installed module, change status
						if ($files[$i]->status == 'PUBLISHED') {
							$this->db()->update($this->table->attachment,array('status'=>'DRAFT'))->where('idx',$files[$i]->idx)->execute();
							$files[$i]->status = 'DRAFT';
							array_push($updates,$files[$i]);
						}
					}
				}
				
				
				
			}
			
			$data->success = true;
			$data->updates = $updates;
		}
		
		if ($action == '@normalize') {
			$mNormalizer = new UnicodeNormalizer();
			$files = $this->db()->select($this->table->attachment)->get();
			for ($i=0, $loop=count($files);$i<$loop;$i++) {
				$files[$i]->name = $mNormalizer->normalize($files[$i]->name);
				$this->db()->update($this->table->attachment,array('name'=>$files[$i]->name))->where('idx',$files[$i]->idx)->execute();
			}
			
			$data->success = true;
		}
		
		$this->IM->fireEvent('afterDoProcess','attachment',$action,$values,$results);
		
		return $results;
	}
}
?>