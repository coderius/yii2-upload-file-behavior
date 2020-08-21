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
 *                        'directories' => [
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
    /**
     * Name of attribute for recording file from form to ActiveRecord. DEfault name of attribute is `file`
     *
     * @var [string]
     */
    public $nameOfAttributeFile = 'file';

    /**
     * @var string
     */
    public $nameOfAttributeStorage = 'face_img';
    
    /**
     * Image name for renaming file and in next saving in file system and db
     *
     * @var [string]
     */
    public $newFileName;

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
     * @var [array]
     */
    public $directories;

    /**
     * File instance populated by yii\web\UploadedFile::getInstance
     *
     * @var null|UploadedFile the instance of the uploaded file.
     */
    private $fileInstance;

    /**
     * Undocumented variable
     *
     * @var [boolean | int]
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

        if ($this->directories === null) {
            throw new InvalidConfigException('The "directories" property must be set.');
        }
        if (!is_array($this->directories)) {
            throw new InvalidConfigException('The "directories" property must be an array.');
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
            ActiveRecord::EVENT_BEFORE_INSERT => 'loadFile',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsertHendler',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdateHendler',
        ];
    }

    /**
     * Undocumented function
     *
     * @param [type] $event
     * @return void
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
     * @param [type] $event
     * @return void
     */
    public function afterInsertHendler($event)
    {
        $this->hendlersReducer(true);
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $event
     * @return void
     */
    public function afterUpdateHendler($event)
    {
        $this->hendlersReducer(false);
    }          
    

    /**
     * Undocumented function
     *
     * @param [type] $insert
     * @return void
     */
    protected function hendlersReducer($insert)
    {
        if ($this->isFile())
        {
            foreach($this->directories as $dir){
                if ($dir['path'] instanceof Closure || (is_array($dir['path']) && is_callable($dir['path']))) {
                    $dirPath = $dir['path']($this->owner->attributes);
                }else{
                    throw new InvalidCallException('Param `path` mast be instanceof Closure or callable method.');
                }
                
                if(!$insert){
                    FileHelper::removeDirectory($dirPath);
                }
                
                FileHelper::createDirectory($dirPath);
                $newFilePath = $dirPath . $this->getNewFileName();

                if ($dir['hendler'] instanceof Closure || (is_array($dir['hendler']) && is_callable($dir['hendler']))) {
                    $dir['hendler']($this->getFileInstance()->tempName, $newFilePath);
                }else{
                    throw new InvalidCallException('Param `hendler` mast be instanceof Closure or callable method.');
                }
            }
        }
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

}