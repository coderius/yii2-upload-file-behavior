<?php
//UploadFileBehavior
namespace tests\unit;

use Yii;
use yii\web\UploadedFile;
use yii\base\InvalidParamException;
use tests\data\model;
use tests\data\model\Article;
use coderius\yii2UploadFileBehavior\UploadFileBehavior;
use yii\helpers\FileHelper;
use yii\base\InvalidConfigException;

class UploadFileBehaviorTest extends \tests\TestCase
{
  
   /**
   * @inheritdoc
   */
  public static function setUpBeforeClass()
  {
      parent::setUpBeforeClass();

      $_FILES = [
          'Article[file]' => [
              'name' => 'test-image.jpg',
              'type' => 'image/jpeg',
              'size' => 74463,
              'tmp_name' => __DIR__ . '/../data/test-image.jpg',
              'error' => 0,
          ],
      ];
  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function tearDownAfterClass()
  {
      fwrite(STDOUT, __METHOD__ . "\n");
      FileHelper::removeDirectory(Yii::getAlias('@uploadsPath'));
  }

  public function testIsFile()
  {
      $file = UploadedFile::getInstanceByName('Article[file]');
      $this->assertTrue($file && $file->tempName);
  }

  public function testGetFileInstance()
  {
      $file = UploadedFile::getInstanceByName('Article[file]');
      $this->assertTrue($file instanceof UploadedFile);
  }

  /**
  * @dataProvider additionProvider
  */
  public function testGetNewFileName($name, $filename, $time)
  {
      $newFileName = "name";
      $file = UploadedFile::getInstanceByName('Article[file]');
      $baseName = $file->baseName;
      $ext = $file->extension;
        
      $result =  $name ?
        $name . '.' . $file->extension :
        $file->baseName . '_' . $time . '.' . $file->extension;

      $this->assertEquals($filename, $result);  
  }

  // /**
  // * @dataProvider additionProvider
  // */
  // public static function testClasst($name, $filename, $time)
  // {
  //     fwrite(STDOUT, $filename . "\n");
  // }

  public function additionProvider()
  {
      $time = time();

      return [
          ["name", "name.jpg", $time],
          ["name-2", "name-2.jpg", $time],
          [false, "test-image_".$time.".jpg", $time],
      ];
  }

  public function testUploadImageSaveRecord()
  {
    $article = new Article();
    $article->text = 'some text';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $res = $article->save();

    $this->assertTrue($res);
    $lastRecord = Article::findOne($article->id);

    $this->assertTrue($lastRecord !== null);

    
  }

  public function testUploadImageFindSavedFile()
  {
    $article = new Article();
    $article->text = 'some text';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $res = $article->save();
    $path = Yii::getAlias('@uploadsPath/' . $article->id . '/' . Article::BIG_SIZE_DIR . '/' . $article->img_src);
    
    $this->assertTrue(is_file($path));
  }

  /**
  * @dataProvider directoriesProvider
  */
  public function testExceptionNotSetDirectoryConfigNotSet($targets, $ex, $mes)
  {
      $this->expectException($ex);
      $this->expectExceptionMessage($mes);
      $article = new Article();
      $article->detachBehaviors();
      $article->attachBehavior('myBehavior', [
          'class' => UploadFileBehavior::className(),
          'nameOfAttributeStorage' => 'img_src',
          'targets' => $targets
      ]);
  }

  public function directoriesProvider()
  {
      $time = time();

      return [
          [null, InvalidConfigException::class, 'The "targets" property must be set.'],
          [false,InvalidConfigException::class, 'The "targets" property must be set.'],
          ['',   InvalidConfigException::class, 'The "targets" property must be set.'],
          ['123',InvalidConfigException::class, 'The "targets" property must be an array.'],
      ];
  }

  public function testDefaultDeleteImageWithRecord()
  {
      $article = new Article();
      $article->text = 'some text';
      $article->file = UploadedFile::getInstanceByName('Article[file]');
      $res = $article->save();
      $lastRecord = Article::findOne($article->id);
      $path = Yii::getAlias('@uploadsPath/' . $article->id . '/' . Article::BIG_SIZE_DIR . '/' . $article->img_src);
      $this->assertTrue(is_file($path));
      $article->delete();
      $this->assertTrue(!is_file($path));
  }
  
  public function testDefaultBehavior()
  {
      $defaultScenario = 'default';
      $article = new Article();
      $behavior = $article->getBehavior('uploadFileBehavior');

      $this->assertEquals($defaultScenario, $behavior->scenarios[0]);
  }

}