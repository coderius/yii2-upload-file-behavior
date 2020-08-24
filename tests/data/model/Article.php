<?php

namespace tests\data\model;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use coderius\yii2UploadFileBehavior\UploadFileBehavior;
use yii\imagine\Image;
use Imagine\Image\Point;
use Imagine\Image\Box;
/**
 * This is the model class for table "portfoleo_photo".
 *
 * @property int $id
 * @property string $img_src
 * @property string $text

 */
class Article extends \yii\db\ActiveRecord
{
    public $file;

    const BIG_SIZE_DIR    = "/big/";
    const MIDDLE_SIZE_DIR = "/middle/";
    const SMALL_SIZE_DIR  = "/small/";
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['text', 'required'],
            [['img_src', 'file'], 'safe'],
        ];
    }

    public function behaviors()
    {
        return [
            'uploadFileBehavior' => [
                'class' => UploadFileBehavior::className(),
                'nameOfAttributeStorage' => 'img_src',
                'targets' => [
                    
                    [
                        'path' => function($attributes){
                            return \Yii::getAlias('@uploadsPath/' . $attributes['id'] . self::BIG_SIZE_DIR);
                        },
                        'hendler' => function($fileTempName, $newFilePath){
                            Image::thumbnail($fileTempName, 900, 900*2/3)
                            ->copy()
                            ->crop(new Point(0, 0), new Box(900, 900*2/3))
                            ->save($newFilePath, ['quality' => 80]);
                            sleep(1);
                        }
                    ],
                    [
                        'path' => function($attributes){
                            return \Yii::getAlias('@uploadsPath/' . $attributes['id'] . self::MIDDLE_SIZE_DIR );
                        },
                        'hendler' => function($fileTempName, $newFilePath){
                            Image::thumbnail($fileTempName, 400, 400*2/3)
                            ->save($newFilePath, ['quality' => 80]);
                            sleep(1);
                        }
                    ],
                    [
                        'path' => function($attributes){
                            return \Yii::getAlias('@uploadsPath/' . $attributes['id'] . self::SMALL_SIZE_DIR );
                        },
                        'hendler' => function($fileTempName, $newFilePath){
                            Image::thumbnail($fileTempName, 150, 150*2/3)
                            ->save($newFilePath, ['quality' => 80]);
                            sleep(1);
                        }
                    ],
                ]
            ],

        ];
    }

}
