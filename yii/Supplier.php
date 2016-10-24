<?

/**
 * This is the model class for table "supplier".
 */
class Supplier extends CActiveRecord
{
	/**
	 * Supplier ID in table "supplier".
	 * @var int
	 **/
	public $id;

	/**
	 * Supplier - title.
	 * @var string
	 **/
	public $title;

	/**
	 * Supplier - $slug.
	 * @var string
	 **/
	public $slug;
	public $slug_lt;
	public $slug_ru;
	public $slug_lv;

	/**
	 * Supplier - description.
	 * @var string
	 **/
	public $description;

	/**
	 * User ID. User relating to this Supplier. Administrator(owner).
	 * @var int
	 **/
	public $user_id;

	/**
	 * Supplier - create time.
	 * @var int
	 **/
	public $createtime;

	/**
	 * GPS coordinates.
	 * @var mixed
	 **/
	public $gps;

	/**
	 * Supplier - website.
	 * @var string
	 **/
	public $website;

	/**
	 * Supplier - phone.
	 * @var int
	 **/
	public $phone;

	/**
	 * Supplier - email.
	 * @var string
	 **/
	public $email;

	/**
	 * Supplier - timezone.
	 * @var string
	 **/
	public $timezone;

	/**
	 * Supplier - country ID.
	 * @var string
	 **/
	public $country_id;

	/**
	 * Supplier - city ID.
	 * @var int
	 **/
	public $city_id;

	/**
	 * Supplier - address.
	 * @var string
	 **/
	public $address;

	/**
	 * Supplier - address-2.
	 * @var string
	 **/
	public $address2;

	/**
	 * Supplier - zip.
	 * @var int
	 **/
	public $zip;

	/**
	 * Supplier - language ID.
	 * @var int
	 **/
	public $language_id;

	/**
	 * Supplier - category id.
	 * @var string
	 **/
	public $category_id;

	/**
	 * Supplier - image name.
	 * @var string
	 **/
	public $image_id;

	/**
	 * Supplier - small image name.
	 * @var string
	 **/
	public $small_image;

	/**
	 * If the level is 1. Supplier has been assigned administrator.
	 * @var int
	 **/
	public $level;

	/**
	 * Latitude and longitude.
	 * @var string
	 **/
	public $additional_addresses;

	/**
	 * Set active or inactive activity.
	 * @var boolean
	 **/
	public $status;
	
/*
	description in table `activity_statuses` 
*/
	public $int__status_id;
	
	public $indoor;
	public $outdoor;
	public $child_specific;

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Supplier the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * The associated database table name.
	 * @return string
	 */
	public function tableName()
	{
		return '{{supplier}}';
	}

	/**
	 * Validation rules for model attributes.
	 * @return array
	 */
	public function rules(){
		return array(
			array('title,website,address,address2,phone,zip,slug,slug_lt,slug_ru,slug_lv','filter','filter'=>array($obj=new CHtmlPurifier(),'purify')),
			array('id','unique'),
			array('title','required'),
			array('slug','required'),
			array('title','unique','caseSensitive'=>'false','on'=>'registration'),
			array('zip', 'numerical', 'integerOnly'=>true),
			array('description','required'),
			array('email', 'email'),
			array('website', 'url', 'defaultScheme' => 'http'),
			array('address','required'),
			array('zip','required'),
			array('city_id','required'),
		);
	}

	/**
	 * Action that is performed before validation.
	 * Clears data from unsafe code.
	 */
	protected function beforeValidate()
	{
		if (!empty($this->description))
		{
			$purifier = new CHtmlPurifier();
			$purifier->options = array(
				'HTML.Allowed' => 'p,a[href],b,i,font,ol,ul,li',
			);
			$purifier->purify($this->description);
			$this->description	=stripcslashes($this->description);
		}
		if (!empty($this->title))
		{
			$this->title		=stripcslashes($this->title);
		}
		if (!empty($this->title_lt))
		{
			$this->title_lt		=stripcslashes($this->title_lt);
		}
		return parent::beforeValidate();
	}

	/**
	 * Relations between tables.
	 * @return array relational rules.
	 */
 	public function relations()
	{
		return array
		(
			'language'=>array(self::HAS_ONE, 'Language', array('id' => 'language_id')),
			'media'=>array(self::HAS_ONE, 'Media', array('supplier_id' => 'user_id')),
			'city'=>array(self::HAS_ONE, 'City', array('id' => 'city_id')),
			'target'=>array(self::HAS_MANY, 'Target', array('supplier_id' => 'id')),
			'tag'=>array(self::HAS_MANY, 'Tag', array('supplier_id' => 'id')),
			'category'=>array(self::HAS_ONE, 'Category', array('supplier_id' => 'id')),
			'businesshour'=>array(self::HAS_MANY, 'Business_Hour', array('supplier_id' => 'id')),
			'favourite_supplier'=>array(self::HAS_MANY, 'Favourite_Supplier', array('supplier_id' => 'id')),
			'supplier_access'=>array(self::HAS_MANY, 'Supplier_Access', array('supplier_id' => 'id')),
			'comment'=>array(self::HAS_MANY, 'Comment', array('supplier_id' => 'id')),
			'active_comment'=>array(self::HAS_MANY, 'Comment', array('supplier_id' => 'id'), 'condition' => "active_comment.status = 'active'"),
			'profile'=>array(self::HAS_ONE, 'SupplierProfile', array('supplier_id' => 'id')),
			'translations'=>array(self::HAS_MANY, 'WTranslations', array('supplier_id' => 'id')),
			'paid_translations'=>array(self::HAS_ONE, 'Supplier_translation_paid', array('supplier_id' => 'id')),
			'user'=>array(self::HAS_ONE, 'User', array('id' => 'user_id')),
			'meta'=>array(self::HAS_ONE, 'SupplierMeta', array('supplier_id' => 'id')),
			'obj__additional_addresses'=>array(self::HAS_MANY, 'ActivityAdditionalAddress', array('activity_id' => 'id')),
		);
	}

	/**
	 * Check whether the duplicate supplier name.
	 * @return boolean
	 */
	public function checkDuplicateSupplier()
	{
		$is_exist=false;
		if ($this->isNewRecord) {
			foreach (__Language::$_arr_int__language_id as $var__key =>$var__value)
			{
				if($var__key=='en')
					$var_slug='slug';
				else
					$var_slug='slug_'.$var__key;
				$obj__criteria = new CDbCriteria;
				$obj__criteria->with=array('category');
				$obj__criteria->compare('category.category_id', $_POST['ActivityField']['category_id']);
				$obj__criteria->compare($var_slug,$this[$var_slug]);
				$obj__criteria->together = true;

				if(Supplier::model()->exists($obj__criteria))
				{
					$is_exist=true;
				}
				$arr__result=
				[
					'lang'=>$var__key,
					'is_exist'=>$is_exist,
				];
				return $arr__result;
			}
		} else {
			foreach (__Language::$_arr_int__language_id as $var__key =>$var__value)
			{
				if($var__key=='en')
					$var_slug='slug';
				else
					$var_slug='slug_'.$var__key;
				$obj__criteria = new CDbCriteria;
				$obj__criteria->with=array('category');
				$obj__criteria->compare('category.category_id', $_POST['ActivityField']['category_id']);
				$obj__criteria->compare($var_slug,$this[$var_slug]);
				$obj__criteria->addCondition('t.id != '.$this->id);
				$obj__criteria->together = true;

				if(Supplier::model()->exists($obj__criteria))
				{
					$is_exist=true;
				}
				$arr__result=
					[
						'lang'=>$var__key,
						'is_exist'=>$is_exist,
					];
				return $arr__result;
			}
		}
	}
	
	
	static function GetGalleryImages($int__activity_id, $vchar__media_type)
	{
		$arr_obj__Media	=Media::model()->findAll
		(
			[
				'condition'	=>
				"
					type 		=:vchar__media_type 
				AND supplier_id =:int__activity_id
				",
				'params'	=>
				[
					':int__activity_id'		=>$int__activity_id,
					':vchar__media_type'	=>$vchar__media_type
				]
			]
		);
		
		$arr_arr__result	=[];
		foreach ($arr_obj__Media as $var__value)
			$arr_arr__result[]=
			[
				'str__image_filename'	=>$var__value->filename,
				'str__image_path'		=>STR__activity_images_repository_path.$var__value->filename,
				'str__small_image_path'	=>STR__activity_images_repository_path.$var__value->small_image
			];
		
	   
			
		return $arr_arr__result;
	}
	
	static function GetSEOLinkById($int__id, $vchar__mode ='')
	{
		$obj__Supplier	=Supplier::model()->find(array("condition" =>"id=" .$int__id));
		
		if($obj__Supplier->category->category_name->name == 'Other')
		{
			$str__prefix	='/';
			if (!empty($obj__Supplier->category->category_name->parent_category->name))
				$str__prefix	.=strtolower(str_replace(' ', '-', $obj__Supplier->category->category_name->parent_category->name)) .'/';
			if (!empty($obj__Supplier->category->category_name->name))
				$str__prefix	.=strtolower(str_replace(' ', '-', $obj__Supplier->category->category_name->name)) .'/';

			if(Yii::app()->owner->language=='en')
			{
				return  $str__prefix .CHtml::encode($obj__Supplier['slug']);
			}
			else
			{
				if($obj__Supplier['slug_'.Yii::app()->owner->language])
				{
					return  $str__prefix .CHtml::encode($obj__Supplier['slug_'.Yii::app()->owner->language]);
				}
				else
				{
					return  $str__prefix .CHtml::encode($obj__Supplier['slug']);
				}
			}
		}
		else
		{
			$str__prefix	='/';
			if (!empty($obj__Supplier->category->category_name->name))
			{
				$str__prefix	.=strtolower(str_replace(' ', '-', $obj__Supplier->category->category_name->name)) .'/';
			}
			else if ($vchar__mode ==='admin')
			{
				return '/supplier/edit/supplier_id/' .$int__id;
			}

			if(Yii::app()->owner->language=='en')
			{
				return  $str__prefix .CHtml::encode($obj__Supplier['slug']);
			}
			else
			{
				if($obj__Supplier['slug_'.Yii::app()->owner->language])
				{
					return  $str__prefix .CHtml::encode($obj__Supplier['slug_'.Yii::app()->owner->language]);
				}
				else
				{
					return  $str__prefix .CHtml::encode($obj__Supplier['slug']);
				}
			}
		}
	}

	public function scopes()
	{
		return array(
			'published'=>array(
				'condition'=>"status='true' OR int__status_id=1",
			),
			'draft'=>array(
				'condition'=>"int__status_id=3",
			),
		);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	

}