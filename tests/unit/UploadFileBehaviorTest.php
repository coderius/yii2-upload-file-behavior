<?php
//UploadFileBehavior
namespace tests\unit;

use Yii;
use yii\web\UploadedFile;
use yii\base\InvalidParamException;

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

  public function testGetNewFileName()
  {
      $newFileName = "name";
      $file = UploadedFile::getInstanceByName('User[image]');
      $baseName = $file->baseName;
      $ext = $file->extension;
        
      $result =  $newFileName ?
        $newFileName . '.' . $file->extension :
        $file->baseName . '_' . $this->time . '.' . $file->extension;

      $this->assertEquals('name.jpg', $result);  
  }
  
  public function testSome()
  {
    $this->assertTrue(true);
  }


}