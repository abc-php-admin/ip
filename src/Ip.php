<?php 
namespace abc;
class Ip
{
    private static $instance;
    private $fp;
    private $firstip;
    private $lastip;
    private $totalip;
    private $dict_isp = ['联通', '移动', '铁通', '电信', '长城', '聚友',];
    private $dict_city_directly = ['北京', '天津', '重庆', '上海',];
    private $dict_province = ['北京', '天津', '重庆', '上海', '河北', '山西', '辽宁', '吉林', '黑龙江', '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', '湖北', '湖南', '广东', '海南', '四川', '贵州', '云南', '陕西', '甘肃', '青海', '台湾', '内蒙古', '广西', '宁夏', '新疆', '西藏', '香港', '澳门',];

    private final function __construct($filepath = null)
    {
        $this->init($filepath);
    }
    public static function update(){
        ini_set('max_execution_time', 7200);
        ini_set("memory_limit", "2048M");
    	$copywrite = file_get_contents("http://update.cz88.net/ip/copywrite.rar");
    	$qqwry = file_get_contents("http://update.cz88.net/ip/qqwry.rar");
    	$key = unpack("V6", $copywrite)[6];
    	for($i=0; $i<0x200; $i++)
    	{
    		$key *= 0x805;
    		$key ++;
    		$key = $key & 0xFF;
    		$qqwry[$i] = chr( ord($qqwry[$i]) ^ $key );
    	}
    	$qqwry = gzuncompress($qqwry);
    	$fp = fopen( __DIR__ . '/qqwry.dat', "wb");
    	if($fp)
    	{
    		fwrite($fp, $qqwry);
    		fclose($fp);
    	}

    }
    private function init($filepath)
    {
        $filename = __DIR__ . '/qqwry.dat';
        if ($filepath) {
            $filename = $filepath;
        }
        if (!file_exists($filename)) {
            trigger_error("Failed open ip database file!");
            return;
        }
        $this->fp = 0;
        if (($this->fp = fopen($filename, 'rb')) !== false) {
            $this->firstip = $this->getlong();
            $this->lastip = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        }
    }

    private function getlong()
    {
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }

    public static function find($ip, $filepath = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($filepath);
        }
        return self::$instance->getAddr($ip);
    }

    private function getAddr($ip)
    {
        $result = [];
        $is_china = false;
        $seperator_sheng = '省';
        $seperator_shi = '市';
        $seperator_xian = '县';
        $seperator_qu = '区';
        if (!$this->isValidIpV4($ip)) {
            $result['error'] = 'ip invalid';
            return $result;
        } else {
            $location = $this->getlocationfromip($ip);
            if (!$location) {
                $result['error'] = 'file open failed';
                return $result;
            }
            $location['org_country'] = $location['country'];
            $location['org_area'] = $location['area'];
            $location['province'] = $location['city'] = $location['county'] = '';
            $_tmp_province = explode($seperator_sheng, $location['country']);
            if (isset($_tmp_province[1])) {
                $is_china = true;
                $location['province'] = $_tmp_province[0];
                if (strpos($_tmp_province[1], $seperator_shi) !== false) {
                    $_tmp_city = explode($seperator_shi, $_tmp_province[1]);
                    $location['city'] = $_tmp_city[0] . $seperator_shi;
                    if (isset($_tmp_city[1])) {
                        if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                            $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                            $location['county'] = $_tmp_county[0] . $seperator_xian;
                        }
                        if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                            $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                            $location['county'] = $_tmp_qu[0] . $seperator_qu;
                        }
                    }
                }
            } else {
                foreach ($this->dict_province as $key => $value) {
                    if (false !== strpos($location['country'], $value)) {
                        $is_china = true;
                        if (in_array($value, $this->dict_city_directly)) {
                            $_tmp_province = explode($seperator_shi, $location['country']);
                            $location['province'] = $_tmp_province[0];
                            if (isset($_tmp_province[1])) {
                                if (strpos($_tmp_province[1], $seperator_qu) !== false) {
                                    $_tmp_qu = explode($seperator_qu, $_tmp_province[1]);
                                    $location['city'] = $_tmp_qu[0] . $seperator_qu;
                                }
                            }
                        } else {
                            $location['province'] = $value;
                            $_tmp_city = str_replace($location['province'], '', $location['country']);
                            $_tmp_shi_pos = mb_stripos($_tmp_city, $seperator_shi);
                            if ($_tmp_shi_pos === 0) {
                                $_tmp_city = mb_substr($_tmp_city, 1);
                            }
                            if (strpos($_tmp_city, $seperator_shi) !== false) {
                                $_tmp_city = explode($seperator_shi, $_tmp_city);
                                $location['city'] = $_tmp_city[0] . $seperator_shi;
                                if (isset($_tmp_city[1])) {
                                    if (strpos($_tmp_city[1], $seperator_xian) !== false) {
                                        $_tmp_county = explode($seperator_xian, $_tmp_city[1]);
                                        $location['county'] = $_tmp_county[0] . $seperator_xian;
                                    }
                                    if (!$location['county'] && strpos($_tmp_city[1], $seperator_qu) !== false) {
                                        $_tmp_qu = explode($seperator_qu, $_tmp_city[1]);
                                        $location['county'] = $_tmp_qu[0] . $seperator_qu;
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if ($is_china) {
                $location['country'] = '中国';
            }
            $location['isp'] = $this->getIsp($location['area']);
            $result['ip'] = $location['ip'];
            $result['country'] = $location['country'];
            $result['province'] = $location['province'];
            $result['city'] = $location['city'];
            $result['county'] = $location['county'];
            $result['isp'] = $location['isp'];
            $result['area'] = $location['country'] . $location['province'] . $location['city'] . $location['county'] . $location['org_area'];
        }
        return $result;
    }

    private function isValidIpV4($ip)
    {
        $flag = false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        return $flag;
    }

    private function getlocationfromip($ip)
    {
        if (!$this->fp) {
            return null;
        }
        $location['ip'] = $ip;
        $ip = $this->packip($location['ip']);
        $l = 0;
        $u = $this->totalip;
        $findip = $this->lastip;
        while ($l <= $u) {
            $i = floor(($l + $u) / 2);
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4));
            if ($ip < $beginip) {
                $u = $i - 1;
            } else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));
                if ($ip > $endip) {
                    $l = $i + 1;
                } else {
                    $findip = $this->firstip + $i * 7;
                    break;
                }
            }
        }
        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong());
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 1:
                $countryOffset = $this->getlong3();
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);
                switch (ord($byte)) {
                    case 2:
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default:
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2:
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default:
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }
        $location['country'] = iconv("GBK", "UTF-8", $location['country']);
        $location['area'] = iconv("GBK", "UTF-8", $location['area']);
        if ($location['country'] == " CZ88.NET" || $location['country'] == "纯真网络") {
            $location['country'] = "无数据";
        }
        if ($location['area'] == " CZ88.NET") {
            $location['area'] = "";
        }
        return $location;
    }

    private function packip($ip)
    {
        return pack('N', intval($this->ip2long($ip)));
    }

    private function ip2long($ip)
    {
        $ip_arr = explode('.', $ip);
        $iplong = (16777216 * intval($ip_arr[0])) + (65536 * intval($ip_arr[1])) + (256 * intval($ip_arr[2])) + intval($ip_arr[3]);
        return $iplong;
    }

    private function getlong3()
    {
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));
        return $result['long'];
    }

    private function getstring($data = "")
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) {
            $data .= $char;
            $char = fread($this->fp, 1);
        }
        return $data;
    }

    private function getarea()
    {
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 0:
                $area = "";
                break;
            case 1:
            case 2:
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default:
                $area = $this->getstring($byte);
                break;
        }
        return $area;
    }

    private function getIsp($str)
    {
        $ret = '';
        foreach ($this->dict_isp as $k => $v) {
            if (false !== strpos($str, $v)) {
                $ret = $v;
                break;
            }
        }
        return $ret;
    }

    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }
}