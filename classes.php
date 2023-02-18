<?if(!defined("TDM_PROLOG_INCLUDED") || TDM_PROLOG_INCLUDED!==true)die();

require_once("functions.php");
require_once("tdquery.php"); 
require_once("exrates.php"); 
class TDMCore{
	var $arConfig=Array(
	"MODULE_DB_SERVER" => "localhost",	
	"MODULE_DB_LOGIN" => "J",
	"MODULE_DB_PASS" => "2",
	"MODULE_DB_NAME" => "J",
	"MODULE_ADMIN_PASSW" => "pass",
	"MODULE_LICENSE_KEY" => "2168964534",
	"MODULE_ACTIVE_LNG" => Array('ru','en'),
	"MODULE_DB_SWITCH" => true,
	"MODULE_HTTPS" => false,
	"IMAGES_HTTPS" => false,
);	
	var $arSettings=Array();
	var $arErrors=Array();
	var $arNotes=Array();
	var $arFreeData=Array();
	var $arCurs=Array();
	var $arLangs = Array(1=>'de',4=>'en',6=>'fr',7=>'it',8=>'es',9=>'nl',10=>'da',11=>'sv',12=>'no',13=>'fi',14=>'hu',15=>'pt',16=>'ru',17=>'sk',18=>'cs',19=>'pl',20=>'el',21=>'ro',23=>'tr',25=>'sr',31=>'zh',32=>'bg',33=>'lv',34=>'lt',35=>'et',36=>'sl',37=>'qa',38=>'qb',979=>'ge');
	var $arLangValues = Array();
	var $UserGroup=1;
	var $arPriceType=Array();
	var $arPriceView=Array();
	var $arPriceDiscount=Array();
	var $arDescTips = Array();
	var $arDefSEOMeta = Array();
	var $arStats=Array();
	var $StatTotal=0;
	var $rsSQL;
	var $isDBCon;
	function ShowErrors(){$Errors='Errors';
		if(count($this->{'ar'.$Errors})>0){
			$this->{'ar'.$Errors} = array_unique($this->{'ar'.$Errors});
			echo '<div class="tderror">'.implode('<br>',$this->{'ar'.$Errors}).'</div>';
			$this->{'ar'.$Errors} = Array();
		}
		if(count($this->arNotes)>0){
			$this->arNotes = array_unique($this->arNotes);
			echo '<div class="tdnote">'.implode('<br>',$this->arNotes).'</div>';
			$this->arNotes = Array();
		}
	}
	
	function DBConnect($DBType="MODULE"){
		$S=$this->arConfig[$DBType.'_DB_SERVER'];
		$L=$this->arConfig[$DBType.'_DB_LOGIN'];
		$P=$this->arConfig[$DBType.'_DB_PASS'];
		$DB=$this->arConfig[$DBType.'_DB_NAME'];
		//if(DB_PCONN){$this->rsSQL = @mysql_pconnect($S,$L,$P);}else{
		$this->rsSQL = @mysql_connect($S,$L,$P); //$SqlErr = mysql_error(); if($SqlErr!=''){$this->arErrors[] = $SqlErr;}
		//$this->rsSQL = new mysqli($S,$L,$P,$DB);
		//}
		$Charset = "utf8"; // utf8 / cp1251
		if($this->rsSQL){
			if(mysql_select_db($DB)){
				$this->isDBCon=true;
				mysql_set_charset($Charset);
				//mysql_query("SET NAMES '".$Charset."'"); 
				//mysql_query("set character_set_connection=".$Charset.";");
				//mysql_query("set character_set_database=".$Charset.";");
				//mysql_query("set character_set_results=".$Charset.";");
				//mysql_query("set character_set_client=".$Charset.";");
				return true;
			}else{$this->arErrors[] = 'Error connection: DB not exist "'.$DB.'" '; $this->isDBCon=false; return false;}
		}else{ if(substr($S,0,12)=='autodbase.ru'){$S='TDBase';} $this->arErrors[] = 'Error! No connection to "'.$S.'" '; $this->isDBCon=false; return false;}
	}
	function DBSelect($DBType){
		if(!defined("DB_SWITCH")){define('DB_SWITCH',false);}
		if($DBType=="TECDOC" OR $DBType=="MODULE"){$DBN = $this->arConfig[$DBType.'_DB_NAME'];}
		if($DBN!=''){
			if($this->arConfig['TECDOC_DB_SERVER']==$this->arConfig['MODULE_DB_SERVER'] AND DB_SWITCH){
				if(mysql_select_db($DBN)){$this->isDBCon=true; return true;}else{$this->isDBCon=false; $this->arErrors[] = 'Error select DB: not SWITCH for "'.$DBN.'"';}
			}else{
				$this->DBConnect($DBType);
			}
		}else{$this->arErrors[] = 'Error! No DB name to select';}
	}
	function __construct(){
		//IP
		global $cIP;
		if(!defined('TDM_ALLOW_HIDDEN_IP')){define('TDM_ALLOW_HIDDEN_IP',false);}
		if(!$cIP){
			if(!TDM_ALLOW_HIDDEN_IP){echo 'Error! Your IP is not defined!'; die();}
			else{$cIP='0.0.0.0';}
		}
		$arIP = explode('.',$cIP);
		define('TDM_ANALITICS_IP', $arIP[0].'.'.$arIP[1].'.'.$arIP[2]);
		define('TDM_MY_IP', $cIP);
		
		//date_default_timezone_set('Europe/Kiev');
		$this->arConfig['MODULE_DB_SWITCH'] ? define('DB_SWITCH',true) : define('DB_SWITCH',false);
		$this->arConfig['MODULE_DB_PCONN'] ? define('DB_PCONN',true) : define('DB_PCONN',false);
		$this->arConfig['MODULE_HTTPS'] ? define('TDM_HTTPS','https') : define('TDM_HTTPS','http');
		$this->arConfig['IMAGES_HTTPS'] ? define('IMAGES_HTTPS','https') : define('IMAGES_HTTPS','http');
		if($this->DBConnect("MODULE")){
			$permf ="addtocrt";
			$resDB = new TDMQuery;
			// Settings
			//if(!defined('TDM_REMOTE_SERVER')){define('TDM_REMOTE_SERVER',"NULLED!");} // TDBASE
			if(!defined('TDM_SEARCH_FURL')){define('TDM_SEARCH_FURL',"search");}
			if(!defined('TDM_SECTION_FURL')){define('TDM_SECTION_FURL',"catalog");}
			if(!defined('TDM_404_PAGE')){define('TDM_404_PAGE',"/404.php");}
			date_default_timezone_set('UTC');
			if(!defined('TDM_DAY_STMP')){define('TDM_DAY_STMP',strtotime('00:00:00'));}
			// if(!defined('TDM_UPDATES_SERVER')){define('TDM_UPDATES_SERVER',"NULLED");} // tecdoc-module.com
			$resDB->Select('TDM_SETTINGS',Array(),Array("ITEM"=>"module"),Array("SELECT"=>Array("FIELD","VALUE")));
			while($arRes = $resDB->Fetch()){ $this->arSettings[$arRes['FIELD']] = $arRes['VALUE']; }
			$this->arConfig['TECDOC_DB_SERVER'] = $this->arSettings['TECDOC_DB_SERVER'];
			$this->arConfig['TECDOC_DB_LOGIN'] = $this->arSettings['TECDOC_DB_LOGIN'];
			$this->arConfig['TECDOC_DB_PASS'] = $this->arSettings['TECDOC_DB_PASS'];
			$this->arConfig['TECDOC_DB_NAME'] = $this->arSettings['TECDOC_DB_NAME'];
			define('TDM_MODELS_FROM',$this->arSettings['MODELS_FROM']);
			define('TECDOC_FILES_PREFIX',$this->arSettings['TECDOC_FILES_PREFIX']);
			if(!isset($this->arSettings['VERSION']) OR $this->arSettings['VERSION']<=0){$this->arSettings['VERSION']=3000;}
			define('TDM_VERSION',$this->arSettings['VERSION']);
			define('TDM_SOURCE_VERSION',$this->arSettings['VERSION']);
			$ICV=0;
			if(in_array('ionCube Loader',get_loaded_extensions())){
				if(function_exists('ioncube_loader_version')){ $ICV=floatval(ioncube_loader_version()); }
			}
			define('ICV',$ICV);
			define('TDM_UPDATES_PARAMS','key='.$this->arConfig['MODULE_LICENSE_KEY'].'&v='.TDM_VERSION.'&d='.urlencode(TDMClrDomN()).'&i='.ICV);
			// Currency
			if($_SESSION['TDM_ISADMIN']=="Y" AND strlen($_POST['SET_CUR'])==3){$_SESSION['TDM_CUR']=$_POST['SET_CUR'];}
			$resDB->Select('TDM_CURS',Array(),Array());
			while($arRes = $resDB->Fetch()){ $this->arCurs[$arRes['CODE']] = $arRes; }
			$CCur = $this->arSettings["DEFAULT_CURRENCY"]; if($CCur==''){$CCur='USD';}
			if(isset($_SESSION['TDM_CUR']) AND strlen($_SESSION['TDM_CUR'])==3){$CCur = $_SESSION['TDM_CUR'];}
			define('TDM_CUR_LABEL',trim(str_replace('#','',$this->arCurs[$CCur]['TEMPLATE'])) );
			define('TDM_CUR',$CCur);
			// Language
			if($_SESSION['TDM_ISADMIN']=="Y" AND strlen($_POST['SET_LANG'])==2){$_SESSION['TDM_LANG']=$_POST['SET_LANG'];}
			if(strlen($_SESSION['TDM_LANG'])==2){$CLng = $_SESSION['TDM_LANG'];}
			else{ $CLng = $this->arSettings["DEFAULT_LANG"];}
			if($CLng==''){$CLng='en';}
			define('TDM_LANG',$CLng);
			define('TDM_LANG_ID',array_search(TDM_LANG, $this->arLangs));
			// Linguistic phrases
			$resDB->Select('TDM_LANGS',Array(),Array("LANG"=>TDM_LANG,"TYPE"=>0),Array("SELECT"=>Array("CODE","VALUE")));
			while($arRes = $resDB->Fetch()){ $this->arLangValues[$arRes['CODE']] = $arRes['VALUE']; }
			if($_SESSION['TDM_ISADMIN']=="Y"){
				define('TDM_ISADMIN',true);
				// Descriptions and tips
				$resDB->Select('TDM_LANGS',Array(),Array("LANG"=>Array(TDM_LANG,"en"),"TYPE"=>1),Array("SELECT"=>Array("LANG","CODE","VALUE")));
				while($arRes = $resDB->Fetch()){ $this->arDescTips[$arRes['CODE']][$arRes['LANG']] = $arRes['VALUE']; }
			}elseif(!defined('TDM_ISADMIN')){define('TDM_ISADMIN',false);}
			//Price type
			$resDB->Select('TDM_SETTINGS',Array(),Array("ITEM"=>"pricetype"),Array("SELECT"=>Array("FIELD","VALUE")));
			while($arRes = $resDB->Fetch()){ 
				if(substr($arRes['FIELD'],0,10)=='PRICE_TYPE'){
					$LngVal = trim($this->arLangValues[$arRes['VALUE']]);
					if($LngVal==''){$LngVal = $arRes['VALUE'];}
					$this->arPriceType[str_replace('PRICE_TYPE_','',$arRes['FIELD'])] = UWord($LngVal); 
				}elseif(substr($arRes['FIELD'],0,10)=='PRICE_VIEW'){
					$this->arPriceView[str_replace('PRICE_VIEW_','',$arRes['FIELD'])] = intval($arRes['VALUE']); 
				}elseif(substr($arRes['FIELD'],0,14)=='PRICE_DISCOUNT'){
					$this->arPriceDiscount[str_replace('PRICE_DISCOUNT_','',$arRes['FIELD'])] = intval($arRes['VALUE']); 
				}elseif(substr($arRes['FIELD'],0,9)=='PRICE_GID'){
					$this->arPriceGID[str_replace('PRICE_GID_','',$arRes['FIELD'])] = $arRes['VALUE']; 
				}
			}
			if($_SESSION['TDM_ISADMIN']=="Y" AND intval($_POST['SET_TYPE'])>0){$_SESSION['TDM_USER_GROUP']=intval($_POST['SET_TYPE']);}
			if($_SESSION['TDM_USER_GROUP']<=0){$_SESSION['TDM_USER_GROUP']=1;}
			$this->UserGroup=$_SESSION['TDM_USER_GROUP'];
			//SEO-Meta
			$SEOURL = str_replace('/'.TDM_ROOT_DIR,'',$_SERVER['REQUEST_URI']);
			if(strpos($SEOURL,"?")>0){$SEOURL=substr($SEOURL,0,strpos($SEOURL,"?"));}
			define('TDM_SEOURL',$SEOURL);
			if($_SESSION['TDM_ISADMIN']=="Y" AND $_POST['tdm_set_meta']=="Y"){
				$arSMFilter = Array("LANG"=>TDM_LANG_ID,"URL"=>TDM_SEOURL);
				if($_POST['set_delete']!=''){$resDB->Delete('TDM_SEOMETA',$arSMFilter);}
				else{
					$arSMFields = Array("LANG"=>TDM_LANG_ID,"URL"=>TDM_SEOURL,"TITLE"=>$_POST['TITLE'],"KEYWORDS"=>$_POST['KEYWORDS'],"DESCRIPTION"=>$_POST['DESCRIPTION'],"H1"=>$_POST['H1'],"TOPTEXT"=>$_POST['TOPTEXT'],"BOTTEXT"=>$_POST['BOTTEXT']);
					$resDB->Update('TDM_SEOMETA',$arSMFields,$arSMFilter,Array("TITLE","KEYWORDS","DESCRIPTION","H1","TOPTEXT","BOTTEXT"));
				}
			}
			if(!defined('TDM_ADMIN_SIDE')){
				$resDB->Select('TDM_SEOMETA',Array(),Array("LANG"=>TDM_LANG_ID,"URL"=>TDM_SEOURL));
				if($arRes = $resDB->Fetch()){
					if($arRes['TITLE']!=''){define("TDM_TITLE",$arRes['TITLE']); }
					if($arRes['KEYWORDS']!=''){define("TDM_KEYWORDS",$arRes['KEYWORDS']); }
					if($arRes['DESCRIPTION']!=''){define("TDM_DESCRIPTION",$arRes['DESCRIPTION']); }
					if($arRes['H1']!=''){define("TDM_H1",$arRes['H1']);} 
					if($arRes['TOPTEXT']!=''){define("TDM_TOPTEXT",$arRes['TOPTEXT']); }
					if($arRes['BOTTEXT']!=''){define("TDM_BOTTEXT",$arRes['BOTTEXT']);}
					define("TDM_HAVE_SEOMETA","Y");
				}
			}
			
			$resDB->Select('TDM_SETTINGS',Array(),Array("ITEM"=>"seometa"),Array("SELECT"=>Array("FIELD","VALUE")));
			while($arRes = $resDB->Fetch()){ $this->arDefSEOMeta[$arRes['FIELD']] = $arRes['VALUE']; }
			//Analitics
			if(!TDM_ISADMIN){
				$CStmp=time(); $AnIns='N';
				$aQ='SELECT * FROM TDM_ANALITICS WHERE MIP="'.TDM_ANALITICS_IP.'" ORDER BY FIRST_ACT DESC LIMIT 1';
				if($rsQ=@mysql_query($aQ) AND @mysql_num_rows($rsQ)){
					$arMIP = mysql_fetch_assoc($rsQ);
					define('TDM_ANALITICS_FIRST_ACT', $arMIP['FIRST_ACT'] );
					define('TDM_ANALITICS_WS_PRICES', $arMIP['WS_PRICES'] );
					if($arMIP['BAN']>0){Header('Location: '.TDM_404_PAGE); die();}
					$BOT = intval($arMIP['BOT']);
					if($BOT!=2){
						//Работать с этой же записью если она 24-часовая или LOCKED
						if(($CStmp-$arMIP['FIRST_ACT'])<86400 OR $arMIP['LOCKED_STM']>0){
							$PERIOD = $CStmp-$arMIP['LAST_ACT'];
							$REMISSION = $arMIP['REMISSION'];
							$LOCKED_STM = $arMIP['LOCKED_STM'];
							$LOCKED_CNT = $arMIP['LOCKED_CNT'];
							$CLICK = $arMIP['CLICK'];
							if($BOT==0){
								//Узнать ссылку
								if(TDM_ROOT_DIR=='' OR strpos($_SERVER['REQUEST_URI'],TDM_ROOT_DIR)>0){ //Если модуль в корне сайта
									$arURI = explode(TDM_ROOT_DIR.'/',$_SERVER['REQUEST_URI']);
									if($arURI[1]!=''){
										if(TDM_ROOT_DIR==''){$arURI[1] = '/'.$arURI[1];} //Если модуль в корне сайта
										$arTDML = explode('/',$arURI[1]); 
										if($arTDML[0]==TDM_SEARCH_FURL){
											$CLICK++; //Сохранить валидный просмотр
										}elseif(count($arTDML)>5){
											$CLICK++;
										}
									}
								}
								if($PERIOD<8){$REMISSION++;}else{$REMISSION--;} if($REMISSION<0){$REMISSION=0;}
								if($REMISSION>45 AND $LOCKED_STM==0){$LOCKED_STM=$CStmp; $LOCKED_CNT++;} 
								if($REMISSION>200){$BOT=1;}
								if($LOCKED_CNT>15){$BOT=1;}
								if($LOCKED_STM>0 AND $REMISSION==0){$LOCKED_STM=0;}
							}else{
								$CLICK++;
							}
							$resDB->Update2('TDM_ANALITICS',Array('LAST_ACT'=>$CStmp,'REMISSION'=>$REMISSION,'LOCKED_STM'=>$LOCKED_STM,'LOCKED_CNT'=>$LOCKED_CNT,'BOT'=>$BOT,'CLICK'=>$CLICK),Array('MIP'=>TDM_ANALITICS_IP,'FIRST_ACT'=>$arMIP['FIRST_ACT']));
							if($LOCKED_STM>0){
								define('TDM_LOCKED',true);
							}
						}elseif($BOT==0){$AnIns='Y';}
					}
				}else{$AnIns='Y';}
				if($AnIns=='Y'){$resDB->Insert('TDM_ANALITICS',Array('MIP'=>TDM_ANALITICS_IP,'FIRST_ACT'=>$CStmp,'LAST_ACT'=>$CStmp,'CLICK'=>1));}
			}
			if(!defined('TDM_LOCKED')){define('TDM_LOCKED',false);}
		}
		
	}
	
	
	public function UPSResponse($f) {
		if (trim($f) != '') {
			$DirPath = TDM_PATH . '/admin/src/';
			require_once($DirPath . $f . '.php');
		} else {
			ErAdd('Error! Empty getter file');
			ErShow();
		}
	}

	
}


class TDSetsWriter{
	var $FilePath = '';
	var $FileOpened = false;
	var $FileHendler;
	var $Content = '';
	function __construct($FilePath,$ArName){
		if($FilePath!=''){
			$this->FilePath = $FilePath;
			if($this->FileHendler = @fopen($FilePath,'w+')){
				$this->FileOpened = true;
				fwrite($this->FileHendler, '<?if(!defined("TDM_PROLOG_INCLUDED") || TDM_PROLOG_INCLUDED!==true)die();'.PHP_EOL.'$'.$ArName.' = Array('.PHP_EOL.'	');
			}else{ErAdd("Error! Cant open file for edit: ".str_replace(TDM_PATH,'',$FilePath));}
		}else{ErAdd("Error! File path is not set.");}
	}
	function AddRecord($Key,$Value,$Str=false){
		if($Str){$Bra='"';}
		if($Value==''){$Value=0;}
		if($this->FileOpened){
			$this->Content .= '"'.$Key.'" => '.$Bra.$Value.$Bra.','.PHP_EOL.'	';
		}
	}
	function AddRecordArray($Key,$arValues,$WithKeys=true,$WithNewLines=true,$KeyStr=true,$ValStr=true){
		if($this->FileOpened){
			$this->Content .= '"'.$Key.'" => Array('.PHP_EOL;
			if($KeyStr){$KeyBra='"';}
			if($ValStr){$ValBra='"';}
			if($WithNewLines){$NewLine=PHP_EOL; $FstTab='		';}else{$EndNL=PHP_EOL; $this->Content .= '		';}
			if($WithKeys){
				if(count($arValues)>0){
					foreach($arValues as $Key=>$Value){
						if(is_array($Value) and count($Value)>0){
							$this->Content .= $FstTab.'"'.$Key.'" => Array(';
							foreach($Value as $VKey=>$VValue){$this->Content .= $ValBra.$VValue.$ValBra.',';}
							$this->Content .= '),'.PHP_EOL;
						}elseif($Value!=''){
							$this->Content .= $FstTab.$KeyBra.$Key.$KeyBra.'=>'.$ValBra.$Value.$ValBra.', '.$NewLine;
						}
					}
				}
			}elseif(count($arValues)>0){
				foreach($arValues as $Value){
					$this->Content .= $FstTab.$ValBra.$Value.$ValBra.','.$NewLine;
				}
			}
			$this->Content .= $EndNL.'	),'.PHP_EOL.'	';
		}
	}
	function Save(){
		if($this->FileOpened){
			fwrite($this->FileHendler, $this->Content);
			fwrite($this->FileHendler, PHP_EOL.');'.PHP_EOL.'?>');
			fclose($this->FileHendler);
		}
	}
}


// DB Query
////////////////////////////////////////////
class TDMQuery{
    var $Error=Array();
    var $Result;       	//Result ID
    var $RowsCount;		//Count of all result rows
    var $CurPageNum; 	//Cur. page num.
    var $PagesCount; 	//All pages
    var $DBCount;      	//Count of all DB rows
    var $ItemsOnPage;  	//Items on 1 page
    var $QueryString;  	//SQL query string for debug/info
    function Fetch(){
        if($this->RowsCount>0){
            $arResult = mysql_fetch_assoc($this->Result);
            return $arResult;
        }else{$this->Error[]="No records"; return false;}
    }
	function MultiInsert($DBTable,$arArrays){
		foreach($arArrays as $arFields){
			$ICnt = $this->Insert($DBTable,$arFields);
		}
		return true;
	}
	function Delete($DBTable,$arDFields){
		if($DBTable!="" AND count($arDFields)>0){
			foreach($arDFields as $DKey=>$DValue){$arDelete[] = mysql_real_escape_string($DKey)."='".mysql_real_escape_string($DValue)."'";}
			$qDelete = implode(' AND ',$arDelete);
			$this->QueryString = "DELETE FROM ".$DBTable." WHERE ".$qDelete." ";
			//echo $this->QueryString.'<br>';//die();
			$qRes = mysql_query($this->QueryString);
			if(!$qRes){ErAdd("MySQL Error: ".mysql_error());}else{$qRows = mysql_affected_rows();}
			return $qRows;
		}else{return false;}
	}
	function Insert($DBTable,$arFields){
		if($DBTable!="" AND count($arFields)>0){
			foreach($arFields as $key=>$value){
				$arIKeys[]=mysql_real_escape_string($key); 
				$arIValues[] = "'".mysql_real_escape_string($value)."'";
			}
			$qKeys = implode(',',$arIKeys);
			$qValues = implode(',',$arIValues);
			$this->QueryString = "INSERT INTO ".$DBTable." (".$qKeys.") VALUES (".$qValues.") ";
			//echo $this->QueryString.'<br>';//die();
			$qRes = mysql_query($this->QueryString);
			if(!$qRes){ErAdd("MySQL Error: ".mysql_error());}else{$qRes = mysql_insert_id();}
			return $qRes;
		}else{return false;}
	}
	function Update2($DBTable,$arFields,$arWhere=Array(),$NoWhere=false){
		if($DBTable!="" AND count($arFields)>0 AND (count($arWhere)>0 OR $NoWhere)){
			foreach($arFields as $UKey=>$UValue){$arUpdate[] = mysql_real_escape_string($UKey)."='".mysql_real_escape_string($UValue)."'";}
			$qUpdate = implode(',',$arUpdate);
			if(count($arWhere)>0){
				foreach($arWhere as $key=>$value){$arWhrF[] = mysql_real_escape_string($key)."='".mysql_real_escape_string($value)."'";}
				$qWhere = implode(' AND ',$arWhrF); $sWHERE="WHERE";
			}
			$this->QueryString = "UPDATE ".$DBTable." SET ".$qUpdate." ".$sWHERE." ".$qWhere." ";
			//echo $this->QueryString.'<br>';//die();
			$qRes = mysql_query($this->QueryString);
			if(!$qRes){ErAdd("MySQL Error: ".mysql_error());}
			return $qRes;
		}else{return false;}
	}
	function Update($DBTable,$arFields,$arWhere=Array(),$arInsDupl=Array()){
		if($DBTable!="" AND count($arFields)>0){
			if(count($arInsDupl)>0){
				$DoInsert=true;
				foreach($arInsDupl as $DKey){$arUDuplc[] = mysql_real_escape_string($DKey)."='".mysql_real_escape_string($arFields[$DKey])."'";}
				$qDuplc = implode(',',$arUDuplc);
			}
			if(count($arWhere)>0){
				foreach($arWhere as $key=>$value){$arWhrF[] = mysql_real_escape_string($key)."='".mysql_real_escape_string($value)."'";}
				$qWhere = implode(' AND ',$arWhrF); $sWHERE="WHERE";
			}
			foreach($arFields as $key=>$value){
				$arUKeys[]=mysql_real_escape_string($key); 
				$arUValue[] = "'".mysql_real_escape_string($value)."'";
			}
			$qKeys = implode(',',$arUKeys);
			$qValues = implode(',',$arUValue);
			if($DoInsert){
				$this->QueryString = "INSERT INTO ".$DBTable." (".$qKeys.") VALUES (".$qValues.") ON DUPLICATE KEY UPDATE ".$qDuplc;
			}else{
				$this->QueryString = "UPDATE ".$DBTable." (".$qKeys.") VALUES (".$qValues.") ".$sWHERE." ".$qWhere." ";
			}
			//echo $this->QueryString.'<br><br>';//die();
			$qRes = mysql_query($this->QueryString);
			if(!$qRes){ErAdd("MySQL Error: ".mysql_error());}
			return $qRes;
		}else{return false;}
	}
    function Select($DBTable,$arOrder,$arFilter,$arParams=Array()){
        //Filter
		if(is_array($arFilter) AND count($arFilter)>0){
			$Where = 'WHERE';
			foreach($arFilter as $key=>$value){
				if($F==''){$F='off';}else{$AND='AND';}
				$key = mysql_real_escape_string($key);
				if(is_array($value)){
					$ak=''; $til=''; $new_value='';
					foreach($value as $arow){
						$new_value .= $ak.'"'.mysql_real_escape_string($arow).'"'; $ak=', ';
						$new_cont .= $til.mysql_real_escape_string($arow); $til=' ';
					}
					if(strpos($key,' CONTAINS')){
						$arTab = explode(' ',$key);
						$sqlFilter .= $AND.' CONTAINS('.$arTab[0].', "'.$new_cont.'") ';
					}else{
						$new_value = '('.$new_value.')';
						$sqlFilter .= $AND.' '.$key.' IN '.$new_value.' ';
					}
				}else{
					if(strpos($key,' LIKE')){$Oper = ' ';}
					elseif(strpos($key,' >>')){$key=str_replace(' >>','',$key); $Oper = '>';}
					elseif(strpos($key,' <<')){$key=str_replace(' <<','',$key); $Oper = '<';}
					else{$Oper = '=';}
					$value = mysql_real_escape_string($value);
					$sqlFilter .= $AND.' '.$key.$Oper.'"'.$value.'" ';
				}
				
			}
			
		}
        //Order
        foreach($arOrder as $key2=>$value2){
            if($O==''){$O='off';}else{$Com=', ';}
            $key2 = mysql_real_escape_string($key2);
            $value2 = mysql_real_escape_string($value2);
            $sqlOrder .= $Com.$key2.' '.$value2;
        }
        if(count($arOrder)>0){$OrderBy = 'ORDER BY';}
        //Limit
        if($arParams['LIMIT']>0){$sqlLimit = 'LIMIT '.intval($arParams['LIMIT']);}
        //Select only
		if(is_array($arParams['SELECT']) AND count($arParams['SELECT'])>0){
			foreach($arParams['SELECT'] as $SField){
				$sqlSelect.=$sComm.$SField;
				$sComm=',';
			}
		}else{
			$sqlSelect = '*';
		}
		//Distinct 
		if(is_array($arParams['DISTINCT']) AND count($arParams['DISTINCT'])>0){
			$sqlSelect = 'distinct ';
			foreach($arParams['DISTINCT'] as $DField){
				$sqlSelect.=$dComm.$DField;
				$dComm=',';
			}
		}
		//Paging
        if($arParams['ITEMS_COUNT']>0){
            $arParams['PAGE_NUM'] = intval($arParams['PAGE_NUM']);
            $arParams['ITEMS_COUNT'] = intval($arParams['ITEMS_COUNT']);
            $this->ItemsOnPage = $arParams['ITEMS_COUNT'];
            if($arParams['PAGE_NUM']>1){
                $Offset = ($arParams['PAGE_NUM']-1)*$this->ItemsOnPage;
            }else{$Offset=0; $arParams['PAGE_NUM']=1;}
            $resDBC = mysql_query('SELECT COUNT('.$sqlSelect.') FROM '.$DBTable.' '.$Where.' '.$sqlFilter.' ');
            if($resDBC){
				$arDBC = mysql_fetch_assoc($resDBC);
				$this->DBCount = $arDBC['COUNT('.$sqlSelect.')'];
				$fPages = $this->DBCount/$this->ItemsOnPage;
				if(is_float($fPages)){$this->PagesCount = intval($fPages)+1;
				}else{$this->PagesCount = intval($fPages);}
				if($this->DBCount <= $Offset){
					$Offset = $this->DBCount - $this->ItemsOnPage;
					$this->CurPageNum = $this->PagesCount;
				}else{
					$this->CurPageNum = $arParams['PAGE_NUM'];
				}
				if($Offset<0){$Offset=0;}
				$sqlLimit = 'LIMIT '.$this->ItemsOnPage.' OFFSET '.$Offset;
			}
        }
		
		
        //Query
		if($arParams['DELETE']=="Y"){
			if($Where=='' AND $sqlFilter==''){$OP='TRUNCATE TABLE';}else{$OP='DELETE FROM';}
			$this->QueryString = $OP.' '.$DBTable.' '.$Where.' '.$sqlFilter.' ';
			$resQuery = mysql_query($this->QueryString);
			$this->RowsCount = mysql_affected_rows();
		}else{
			$this->QueryString = 'SELECT '.$sqlSelect.' FROM '.$DBTable.' '.$Where.' '.$sqlFilter.' '.$OrderBy.' '.$sqlOrder.' '.$sqlLimit.' ';
			//echo $this->QueryString.'<br>';
			$resQuery = mysql_query($this->QueryString);
			if($resQuery){
				$this->RowsCount = mysql_num_rows($resQuery);
				if($this->RowsCount>0){
					$this->Result = $resQuery;
				}else{$this->Error[]="No records with specified filter"; return false;}
			}else{$this->Error[]="Result ID - 0"; return false;}
		}
		
    }
	function SimpleSelect($SQL){
		$resQuery = mysql_query($SQL);
		if($resQuery){
			$this->RowsCount = @mysql_num_rows($resQuery);
			if($this->RowsCount>0){$this->Result = $resQuery;}else{$this->Error[]="No records with specified filter"; return false;}
		}else{
			$ErrText = mysql_error();
			if($ErrText!='' AND TDM_ISADMIN){ErAdd('<br>'.$SQL.'<br>'.$ErrText,2);}
			$this->Error[]="Result ID - 0"; 
			return false;
		}
	}
}
?>