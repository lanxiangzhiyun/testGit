<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * Redis缓存驱动 
 * 要求安装phpredis扩展：https://github.com/owlient/phpredis
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 */
class CacheRedis extends Cache {

    /**
     * 架构函数
     * @access public
     */
    public function __construct($options='') {
        if ( !extension_loaded('redis') ) {
            throw_exception(L('_NOT_SUPPERT_').':redis');
        }
        if(empty($options)) {
            $options = array (
                'host'          => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
                'port'          => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
                'timeout'       => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
                'persistent'    => false,
                'expire'        => C('DATA_CACHE_TIME'),
                'length'        => 0,
            );
        }
        $this->options =  $options;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new Redis;
        $this->connected = $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /**
     * 是否连接
     * @access private
     * @return boolen
     */
    private function isConnected() {
        return $this->connected;
    }
	//--------------------------------------string(字符串) start-----------------------------------------
    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        return $this->handler->get($name);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolen
     */
    public function set($name, $value, $expire = null) {
        if(is_int($expire)) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        if($result && $this->options['length']>0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolen
     */
    public function del($name) {
        return $this->handler->delete($name);
    }

	/**
     * 自增
     * @access public
     * @param string $name 缓存变量名
	 * @param string $val 自增值，默认值为1
     * @return boolen
     */
    public function incr($name,$val=1) {
        return $this->handler->incrBy($name,$val);
    }
	//--------------------------------------string(字符串) end-----------------------------------------


	//--------------------------------------zset(有序集合) start-----------------------------------------
	/**
     * 向名称为key的zset中添加元素member，sortVal用于排序。如果该元素已经存在，则根据sortVal更新该元素的顺序
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $sortVal 排序值
	 * @param string $member 对象 
     * @return int(操作成功为1，操作失败为0)
     */
	public function zAdd($key,$sortVal,$member){
		return $this->handler->zAdd($key,$sortVal,$member);
	}
	/**
     * 如果在名称为key的zset中已经存在元素member，则该元素的score增加increment；否则向集合中添加该元素，其score的值为increment
     * @access public
     * @param string $key 集合键名
	 * @param string $score 分值
	 * @param string $member 元素
     * @return int 元素更新后的分数值
     */
	public function zIncrBy($key,$score,$member){
		return $this->handler->zIncrBy($key,$score,$member);
	}
	/**
     * 返回名称为key的zset（元素已按sortVal从小到大排序）中的index从start到end的所有元素
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $start 查询的索引开始值，默认值为0
	 * @param string $end 查询的索引结束值，默认值为-1
	 * @param bool $isWithScore 是否输出排序值
     * @return array
     */
	public function zGetByIndexAsc($key,$start=0,$end=-1,$isWithScore=false){
		if($isWithScore){
			return $this->handler->zRange($key,$start,$end,true);
		}else{
			return $this->handler->zRange($key,$start,$end);
		}
	}
	/**
     * 返回名称为key的zset（元素已按sortVal从大到小排序）中的index从start到end的所有元素
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $start 查询的索引开始值，默认值为0
	 * @param string $end 查询的索引结束值，默认值为-1
	 * @param bool $isWithScore 是否输出排序值
     * @return array
     */
	public function zGetByIndexDesc($key,$start=0,$end=-1,$isWithScore=false){
		if($isWithScore){
			return $this->handler->zRevRange($key,$start,$end,true);
		}else{
			return $this->handler->zRevRange($key,$start,$end);
		}
	}
	/**
     * 返回名称为key的zset中score >= start且score <= end的所有元素，已按sort值从小到大排序好了
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $start 查询的排序值开始
	 * @param string $end 查询的排序值结束
	 * @param bool $isWithScore 是否输出排序值，默认为true
	 * @param array $limit 同SQL中的limit，一般情况下用不着
     * @return array
     */
	public function zGetBySortValAsc($key,$start,$end,$isWithScore=true,$limit=null){
		if($isWithScore){
			if(is_array($limit) && count($limit)==2){
				return $this->handler->zRangeByScore($key,$start,$end,array('withscores' => TRUE,'limit'=>array($limit[0],$limit[1])));
			}else{
				return $this->handler->zRangeByScore($key,$start,$end,array('withscores' => TRUE,'limit'=>array($limit[0],$limit[1])));
			}
		}else{
			if(is_array($limit) && count($limit)==2){
				return $this->handler->zRangeByScore($key,$start,$end,array('limit'=>array($limit[0],$limit[1])));
			}else{
				return $this->handler->zRangeByScore($key,$start,$end,array('limit'=>array($limit[0],$limit[1])));
			}
		}
	}
	/**
     * 返回名称为key的zset中score >= star且score <= end的所有元素，已按sort值从大到小排序好了
	 * (有点问题)
     * @access public
     * @param string $key 集合键名
	 * @param string $start 查询的排序值开始
	 * @param string $end 查询的排序值结束
	 * @param bool $isWithScore 是否输出排序值，默认为true
	 * @param array $limit 同SQL中的limit，一般情况下用不着
     * @return array
     */
	public function zGetBySortValDesc($key,$start,$end,$isWithScore=true,$limit=null){
		if($isWithScore){
			if(is_array($limit) && count($limit)==2){
				return $this->handler->zRevRangeByScore($key,$start,$end,array('withscores' => TRUE,'limit'=>array($limit[0],$limit[1])));
			}else{
				return $this->handler->zRevRangeByScore($key,$start,$end,array('withscores' => TRUE,'limit'=>array($limit[0],$limit[1])));
			}
		}else{
			if(is_array($limit) && count($limit)==2){
				return $this->handler->zRevRangeByScore($key,$start,$end,array('limit'=>array($limit[0],$limit[1])));
			}else{
				return $this->handler->zRevRangeByScore($key,$start,$end,array('limit'=>array($limit[0],$limit[1])));
			}
		}
	}
	/**
     * 返回名称为key的zset的所有元素的个数
	 *
     * @access public
     * @param string $key 集合键名
     * @return int 数量
     */
	public function zSize($key){
		return $this->handler->zSize($key);
	}
	/**
     * 返回名称为key的zset中score >= star且score <= end的所有元素的个数
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $start 排序值开始值
	 * @param string $end 排序值结束值
     * @return int 数量
     */
	public function zCount($key,$start,$end){
		return $this->handler->zCount($key,$start,$end);
	}
	/**
     * 返回名称为key的zset中元素member的score
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $member 元素member
     * @return int 分数
     */
	public function zScore($key,$member){
		return $this->handler->zScore($key,$member);
	}
	/**
     * 删除名称为key的zset中score >= star且score <= end的所有元素，返回删除个数
	 *
     * @access public
     * @param string $key 集合键名
	 * @param string $start 排序值开始值
	 * @param string $end 排序值结束值
     * @return int 删除个数
     */
	public function zDeleteByScore($key,$start,$end){
		return $this->handler->zDeleteRangeByScore($key,$start,$end);
	}
	/**
     * 删除名称为key的zset中的元素member
     * @access public
     * @param string $key 集合键名
	 * @param string $member 值
     * @return int(操作成功为1，操作失败为0)
     */
	public function zDelete($key,$member){
		return $this->handler->zDelete($key,$member);
	}
	//--------------------------------------zset(有序集合) start-----------------------------------------


	//--------------------------------------hash(哈希) start-----------------------------------------
	/**
	 * 向名称为name的hash中添加元素key—>value
	 * @param $name hash元素名称
	 * @param $key 元素设定的key键
	 * @param $value  元素设定的value值
	 * @return int(操作成功为1，操作失败为0)
	 */
	public function hSet($name,$key,$value) {
		return $this->handler->hSet($name, $key, $value);
	}

	/**
	 * 返回名称为name的hash中key对应的value
	 * @param $name
	 * @param $key
	 * @return
	 */
	public function hGet($name,$key){
	   return $this->handler->hGet($name,$key);
	}

	/**
	 * 返回名称nameh的hash中元素个数
	 * @param $name
	 * @return mixed
	 */
	public function hLen($name){
		return $this->handler->hLen($name);
	}

	/**
	 * 删除名称为name的hash中键为key的域
	 * @param $name
	 * @return mixed
	 */
	public function hDel($name){
		return $this->handler->hDel($name);
	}

	/**
	 * 返回名称为key的hash中所有键
	 * @param $name
	 * @return mixed
	 */
	public function hKeys($name){
		return $this->handler->hKeys($name);
	}

	/**
	 * 返回名称为name的hash中所有键对应的value
	 * @param $name
	 * @return mixed
	 */
	public function hVals($name){
		return $this->handler->hVals($name);
	}

	/**
	 * 返回名称为name的hash中所有的键（field）及其对应的value
	 * @param $name
	 * @return mixed
	 */
	public function hGetAll($name){
		return $this->handler->hGetAll($name);
	}

	/**
	 * 名称为name的hash中是否存在键名字为key的域
	 * @param $name
	 * @param $key
	 * @return mixed
	 */
	public function hExists($name,$key){
		return $this->handler->hExists($name,$key);
	}

	/**
	 * 将名称为name的hash中key的value增加2
	 * @param $name
	 * @param $key
	 * @param int $val
	 * @return mixed
	 */
	public function hIncrBy($name,$key,$val=2){
		return $this->handler->hIncrBy($name,$key,$val);
	}

	/**
	 * 向名称为key的hash中批量添加元素
	 * @param $name
	 * @param $arrKeyVal
	 * @return mixed
	 */
	public function hMset($name, $arrKeyVal){
		return $this->handler->hMset($name, $arrKeyVal);
	}

	/**
	 * 返回名称为name的hash中key数组对应的value
	 * @param $name
	 * @param $arrKey
	 * @return mixed
	 */
	public function hMGet($name, $arrKey){
		return $this->handler->hMset($name, $arrKey);
	}


		//--------------------------------------hash(哈希) end-----------------------------------------

	//--------------------------------------SET (无顺集合)start-----------------------------------------
	/**
	 * 新增元素
	 * 向名称为key的set中添加元素value,如果value存在，不写入，return false
	 * @access public
	 * @param $key 名称为key的集合
	 * @param $val  添加的值
	 * @return (成功返回 true 失败返回 false)
	 */
	public function sAdd($key,$val) {
		 return $this->handler->sAdd($key,$val);
	}

	/**
	 * 删除元素
	 * 删除名称为key的set中的元素value  ( sRemove)
	 * @access public
	 * @param $key 名称为key的集合
	 * @param $val  删除的值
	 * @return (成功返回 true 失败返回 false)
	 */
	public function sRem($key,$val) {
		return  $this->handler->sRem($key,$val);
	}

	/**
	 * 移动元素
	 * 将value元素从名称为srckey的集合移到名称为dstkey的集合
	 * @access public
	 * @param $seckey 名称为$seckey的集合
	 * @param $dstkey 名称为$dstkey的集合
	 * @param $val  移动的值
	 * @return (成功返回 true 失败返回 false)
	 */
		public function sMove($seckey, $dstkey, $val) {
			return $this->handler->sMove($seckey, $dstkey, $val);;
		}

	/**
	 * 检测集合元素
	 * 名称为key的集合中查找是否有value元素，有ture 没有 false
	 * @access public
	 * @param $key 名称为$key的集合
	 * @param $val 检测的值
	 * @return (成功返回 true 失败返回 false)
	 */
	public function sIsMember($key ,$val) {
		  return $this->handler->sIsMember($key,$val);
	}

	/**
	 * 检测集合元素
	 * 名称为key的集合中查找是否有value元素，有ture 没有 false
	 * @access public
	 * @param $key 名称为$key的集合
	 * @param $val 检测的值
	 * @return (成功返回 true 失败返回 false)
	 */
	public function sContains($key ,$val) {
		return $this->handler->sContains($key,$val);
	}

	/**
	 * 返回名称为key的set的元素个数
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return int 元素个数 (不存在返回 0 )
	 */
	public function sCard($key) {
	   return $this->handler->sCard($key);
	}

	/**
	 * 返回名称为key的set的元素个数
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return int 元素个数  (不存在返回 0 )
	 */
	public function sSize($key) {
		return $this->handler->sSize($key);
	}

	/**
	 * 随机返回并删除名称为key的set中一个元素
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return string  key的set中一个随机元素 (集合为空 返回false)
	 */
	public function sPop($key) {
		   return $this->handler->sPop($key);
	}

	/**
	 * 随机返回名称为key的set中一个元素，不删除
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return string  key的set中一个随机元素 (集合为空 返回false)
	 */
	public function sRandMember($key) {
		  return $this->handler->sRandMember($key);
	}

	/**
	 * 求交集
	 * @access public
	 * @param string/array $mixName 集合键名组合
	 * @return array  (交集为空 返回array())
	 */
	public function sInter($mixName) {
		$arrName = array();
		  if(is_array($mixName)) {
			  $arrMixName = $mixName;
		  }  else if(is_string($mixName)) {
			  $arrMixName = explode(',',$mixName);
		  } else{
			  //To do  other type
			  return $arrName;
		  }
		for ($i=0;$i<count($arrMixName)-1;$i++) {
			$arrName[0] = $arrMixName[0];
			if(empty($arrName)) {
				return array();
			} else {

			}
			if(empty($arrName[0])) {
				$arrName[0] =  $this->handler->sInter($arrMixName[$i],$arrMixName[$i+1]);
			} else{
				$arrName[0] =  $this->handler->sInter($arrName[0],$arrMixName[$i+1]);
			}
			if(empty($arrName[0])) {
				return array() ;
			}
			$arr1 =  $arrName[0];
		}
		return $arr1;

	}

	//求交集并将交集保存到output的集合
	public function sInterStore($output,$mixName) {
		if(is_array($mixName))  {
			$strName = implode(',',$mixName);
		} else  {
			$strName = $mixName;
		}
		return $this->handler->sInterStore($output,$strName);
	}

	/**
	 * 求并集
	 * @access public
	 * @param string/array $mixName 集合键名组合
	 * @return array
	 */
	public function sUnion($mixName) {
		$arrName = array();
		if(is_array($mixName)) {
			$arrMixName = $mixName;
		}  else if(is_string($mixName)) {
			$arrMixName = explode(',',$mixName);
		} else{
			//To do  other type
			return $arrName;
		}
		for ($i=0;$i<count($arrMixName)-1;$i++) {
			if($arrName[0]) {
				$arrName[0] =  $this->handler->sUnion($arrName[0],$arrMixName[$i+1]);
			}  else {
				$arrName[0] =  $this->handler->sUnion($arrMixName[$i],$arrMixName[$i+1]);
			}
			$arr1 =  $arrName[0];
		}
		return $arr1;
	}

	//求并集并将并集保存到output的集合
	public function sUnionStore($mixName) {
		if(is_array($mixName))  {
			$strName = implode(',',$mixName);
		} else  {
			$strName = $mixName;
		}
		return $this->handler->sUnionStore($strName);
	}

	/**
	 * 求差集  该集合是第一个给定集合和其他所有给定集合的差集 。
	 * @access public
	 * @param string/array $mixName 集合键名组合
	 * @return array
	 */
	public function sDiff($mixName) {
		$arrName = array();
		if(is_array($mixName)) {
			$arrMixName = $mixName;
		}  else if(is_string($mixName)) {
			$arrMixName = explode(',',$mixName);
		} else{
			//To do  other type
			return $arrName;
		}
		for ($i=0;$i<count($arrMixName)-1;$i++) {
			if($arrName[0]) {
				$arrName[0] =  $this->handler->sDiff($arrName[0],$arrMixName[$i+1]);
			}  else {
				$arrName[0] =  $this->handler->sDiff($arrMixName[$i],$arrMixName[$i+1]);
			}
			$arr1 =  $arrName[0];
		}
		return $arr1;
	}

	//求差集并将差集保存到output的集合
	public function sDiffStore($output,$mixName) {
		if(is_array($mixName))  {
			$strName = implode(',',$mixName);
		} else  {
			$strName = $mixName;
		}
		return $this->handler->sDiffStore($output,$strName);
	}

	/**
	 * 返回名称为key的set的所有元素
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return array 集合 (不存在返回 array() )
	 */
	public function sMembers($key) {
		 return $this->handler->sMembers($key);
	}

	/**
	 * 返回名称为key的set的所有元素
	 *
	 * @access public
	 * @param string $key 集合键名
	 * @return array 集合 (不存在返回 array() )
	 */
	public function sGetMembers($key) {
		return $this->handler->sGetMembers($key);
	}

	//排序，分页等
	public function sort($name,$arrSort =array()) {
		  return $this->handler->sort($name,$arrSort);
	}
	//--------------------------------------SET end-----------------------------------------
	
	//--------------------------------------system(系统) start---------------------------------------------
    /**
     * 清除缓存(该函数会把整个redis里的数据清空，所以禁止使用)
     * @access public
     * @return boolen
     */
    public function clear() {
        return $this->handler->flushDB();
    }
	//--------------------------------------system(系统) end---------------------------------------------
}