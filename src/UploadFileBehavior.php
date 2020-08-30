<?php
//UploadFileBehavior.php

namespace coderius\yii2UploadFileBehavior;

use Closure;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\imagine\Image;
use Imagine\Image\Point;
use Imagine\Image\Box;

/**
 *  UploadFileBehavior class
 *  UploadFileBehavior helped create hendler for uploading files and images to server simply.
 *  To use UploadFileBehavior, insert the following code to your ActiveRecord class:
 *
 *  ```php
 * 
 *   namespase your/models;
 *
 *   use coderius\yii2UploadFileBehavior\UploadFileBehavior;
 *   use yii\imagine\Image;
 *    use Imagine\Image\Point;
 *    use Imagine\Image\Box;
 *
 *    class YourModel extends \yii\db\ActiveRecord
 *    {
 *        public $file;
 *
 *        //'img_src' - attribute to save path to file in db
 *        public function rules()
 *        {
 *            return [
 *                [['img_src'], 'safe'],
 *        }
 *
 *        ...
 *
 *            public function behaviors()
 *            {
 *                return [
 *                    //Another behaviors
 *                    //...
 *
 *                    'uploadFileBehavior' => [
 *                        'class' => UploadFileBehavior::className(),
 *                        'nameOfAttributeStorage' => 'img_src',
                    
                    
 *                        'targets' => [
 *                            
 *                            [
 *                                'path' => function($attributes){
 *                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/big/');
 *                                },
 *                                'hendler' => function($fileTempName, $newFilePath){
 *                                    Image::thumbnail($fileTempName, 900, 900*2/3)
 *                                    ->copy()
 *                                    ->crop(new Point(0, 0), new Box(900, 900*2/3))
 *                                    ->save($newFilePath, ['quality' => 80]);
 *                                    sleep(1);
 *                                }
 *                            ],
 *                            [
 *                                'path' => function($attributes){
 *                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/middle/');
 *                                },
 *                                'hendler' => function($fileTempName, $newFilePath){
 *                                    Image::thumbnail($fileTempName, 400, 400*2/3)
 *                                    ->save($newFilePath, ['quality' => 80]);
 *                                    sleep(1);
 *                                }
 *                            ],
 *                            [
 *                                'path' => function($attributes){
 *                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/small/');
 *                                },
 *                                'hendler' => function($fileTempName, $newFilePath){
 *                                    Image::thumbnail($fileTempName, 150, 150*2/3)
 *                                    ->save($newFilePath, ['quality' => 80]);
 *                                    sleep(1);
 *                                }
 *                            ],
 *                        ]
 *                    ],
 *
 *                ];
 *            }
 *
 *        ...
 *    }    
 *
 *  ```
 * 
 * @author Sergio Coderius <sunrise4fun@gmail.com>
 * @since 2.0
 */
class UploadFileBehavior extends Behavior
{
    const TYPE_IMAGE = 'image';
    const TYPE_FILE = 'file';//not supported yet
    
    /**
     * Name of attribute for recording file from form to ActiveRecord. DEfault name of attribute is `file`
     *
     * @var string
     */
    public $nameOfAttributeFile = 'file';

    /**
     * @var string
     */
    public $nameOfAttributeStorage = 'face_img';
    
    /**
     * Image name for renaming file and in next saving in file system and db
     *
     * @var string
     */
    public $newFileName = false;

    /**
     * Configuretion array for setting target paths to folders and uploading hendlers like in example:
     *
     * [
     *    'path' => function($attributes){
     *          return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/middle/');
     *     },
     *     'hendler' => function($fileTempName, $newFilePath){
     *           Image::thumbnail($fileTempName, 400, 400*2/3)
     *                 ->save($newFilePath, ['quality' => 80]);
     *            sleep(1);
     *     }
     * ],
     *
     * @var array
     */
    public $targets;


    /**
     * 'default' scenario set by default. 
     * @var array the scenarios in which the behavior will be triggered
     */
    public $scenarios = [];

    /**
     * Flag for delete file with related item in db. Default set to true.
     *
     * @var boolean
     */
    public $deleteImageWithRecord = true;
    
    /**
     * File instance populated by yii\web\UploadedFile::getInstance
     *
     * @var null|UploadedFile the instance of the uploaded file.
     */
    private $fileInstance;

    /**
     * Undocumented variable
     *
     * @var boolean|int
     */
    private $time = false;

        /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!class_exists(Image::class)) {
            throw new NotSupportedException("Yii2-imagine extension is required to use the UploadImageBehavior");
        }

        if ($this->targets == null || $this->targets == false || empty($this->targets)) {
            throw new InvalidConfigException('The "targets" property must be set.');
        }
        if (!is_array($this->targets)) {
            throw new InvalidConfigException('The "targets" property must be an array.');
        }
        if (empty($this->scenarios)) {
            $this->scenarios[] = 'default';
        }

    }

    /**
     * {@inheritdoc}
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $this->time = time();
        $this->setFileInstance();
        
    }

     /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'loadFile',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'loadFile',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsertHendler',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdateHendler',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    protected static function supportedFileTypes(){
        return [
            self::TYPE_IMAGE
        ];
    }

    /**
     * Undocumented function
     *
     *  @return void
     */
    public function loadFile()
    {
        if($this->isFile()){
            $this->owner->file = $this->getFileInstance();//virtual attribute
            $this->owner->{$this->nameOfAttributeStorage} = $this->getNewFileName();
        }
    }
    
    /**
     * Undocumented function
     *
     * @return void
     */
    public function afterInsertHendler($event)
    {
        $this->hendlersReducer(true);
    }
    
    /**
     * Undocumented function
     *
     * @return void
     */
    public function afterUpdateHendler($event)
    {
        $this->hendlersReducer(false);
    }          
    
    /**
     * Undocumented function
     *
     * @param Event $event
     * @return void
     */
    public function afterDelete($event)
    {
        if($this->deleteImageWithRecord){
            foreach($this->targets as $target){
                $dirPath = $this->getPath($target['path']);
                FileHelper::removeDirectory($dirPath);
            }    
            
        }
    } 


    /**
     * Undocumented function
     *
     * @param boolean $insert
     * @return void
     */
    protected function hendlersReducer($insert)
    {
        if ($this->isFile() && $this->inScenario())
        {
            foreach($this->targets as $target){
                $dirPath = $this->getPath($target['path']);
                
                if(!$insert){
                    
                    FileHelper::removeDirectory($dirPath);
                }
                
                FileHelper::createDirectory($dirPath);
                $newFilePath = $dirPath . $this->getNewFileName();

                $this->getHendler($target['hendler'], $this->getFileInstance()->tempName, $newFilePath);
                
            }
        }
        return false;
    }

    /**
     * @param string $path
     * @return string $dirPath
     */
    protected function getPath($path){
        if(is_string($path)){
            $path = rtrim($path, '/') . '/';
            $dirPath = \Yii::getAlias($path);
        }
        elseif ($path instanceof Closure || (is_array($path) && is_callable($path))) {
            $dirPath = $path($this->owner->attributes);
        }else{
            throw new InvalidCallException('Param `path` mast be string instanceof Closure or callable method.');
        }

        return $dirPath;
    }

    protected function getHendler($hendler, $tmp, $path){
        if ($hendler instanceof Closure || (is_array($hendler) && is_callable($hendler))) {
            $hendler($tmp, $path);
        }elseif(is_array($hendler) && array_key_exists('type', $hendler) && array_key_exists('config', $hendler)){
            if(!in_array($hendler['type'], self::supportedFileTypes()))
            {
                throw new InvalidConfigException('File type not supported: ' . $hendler['type']);
            }
            switch($hendler['type']) {
                case self::TYPE_IMAGE:
                    $sizeW = $hendler['config']['size']['width'];
                    $sizeH = $hendler['config']['size']['height'];
                    $quality = $hendler['config']['quality'];
                    Image::thumbnail($tmp, $sizeW, $sizeH)
                            ->save($path, ['quality' => $quality]);
                            sleep(1);
                break;
            }

        }else{
            throw new InvalidCallException('Param `hendler` mast be instanceof Closure ,callable method or array with allowed configs.');
        }

        return true;
    }

    /**
     * Get the instance of the uploaded file.
     *
     * @return  null|UploadedFile
     */ 
    protected function getFileInstance()
    {
        return $this->fileInstance;
    }

    /**
     * Set the instance of the uploaded file.
     *
     * @param  null|UploadedFile  $fileInstance  the instance of the uploaded file.
     *
     * @return  self
     */ 
    protected function setFileInstance()
    {
        $this->fileInstance = UploadedFile::getInstance($this->owner, $this->nameOfAttributeFile);
    }

    /**
     * Get image name for renaming file and in next saving in file system and db
     *
     * @return  [string]
     */ 
    protected function getNewFileName()
    {
        $file = $this->getFileInstance();
        $baseName = $file->baseName;
        $ext = $file->extension;
        
         return $this->newFileName ?
            $this->newFileName . '.' . $file->extension :
            $file->baseName . '_' . $this->time . '.' . $file->extension;
    }

    /**
     * Undocumented function
     *
     * @return boolean
     */
    protected function isFile()
    {
        return  $this->getFileInstance() && $this->getFileInstance()->tempName;
    }

    /**
     * Detect if current model scenario in allowed by behavior
     *
     * @return void
     */
    protected function inScenario()
    {
        $model = $this->owner;
        return in_array($model->scenario, $this->scenarios);
    }

}