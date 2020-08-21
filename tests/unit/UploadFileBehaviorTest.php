<?php
//UploadFileBehavior
namespace tests\unit;

use Yii;
use yii\web\UploadedFile;
use yii\base\InvalidParamException;
use tests\data\model;
use tests\data\model\Article;
use coderius\yii2UploadFileBehavior\UploadFileBehavior;

class UploadFileBehaviorTest extends \tests\TestCase
{
  
   /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $_FILES = [
            'User[image]' => [
                'name' => 'test-image.jpg',
                'type' => 'image/jpeg',
                'size' => 74463,
                'tmp_name' => __DIR__ . '/data/test-image.jpg',
                'error' => 0,
            ],
        ];
    }

  public function testGetFileInstance()
  {
      $file = UploadedFile::getInstanceByName('User[image]');
      $this->assertTrue($file instanceof UploadedFile);
  }

  public function testIsFile()
  {
      $file = UploadedFile::getInstanceByName('User[image]');
      $this->assertTrue($file && $file->tempName);
  }

  /**
  * @dataProvider additionProvider
  */
  public function testGetNewFileName($name, $filename, $time)
  {
      $newFileName = "name";
      $file = UploadedFile::getInstanceByName('User[image]');
      $baseName = $file->baseName;
      $ext = $file->extension;
        
      $result =  $name ?
        $name . '.' . $file->extension :
        $file->baseName . '_' . $time . '.' . $file->extension;

      $this->assertEquals($filename, $result);  
  }
  
  public function additionProvider()
  {
      $time = time();

      return [
          ["name", "name.jpg", $time],
          ["name-2", "name-2.jpg", $time],
          [false, "test-image_".$time.".jpg", $time],
      ];
  }

  public function testUploadImageSaveTrue()
  {
    $article = new Article();
    
    // $data = [
    //   'text' => 'some text',
    //   'img_src' => UploadedFile::getInstanceByName('User[image]')
    // ];

    $article->text = 'some text';
    $article->file = UploadedFile::getInstanceByName('User[image]');
    $res = $article->save();

    // $this->assertEquals($article->img_src, 'test-image.jpg'); 
    $this->assertTrue($res);
  }


}