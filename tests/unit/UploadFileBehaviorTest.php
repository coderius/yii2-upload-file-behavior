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
use yii\base\InvalidCallException;
use Mockery;

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

  public function testNewFileName()
  {
    $article = new Article();
    $article->text = 'some text';
    $article->newFileName = 'image-1';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $article->save();

    $recordOne = Article::findOne($article->id);
    
    $this->assertEquals($recordOne->img_src, 'image-1.jpg', 'assertEquals image-1.jpg');
  }

  public function testUpdateImage()
  {
    $article = new Article();
    $article->text = 'some text';
    $article->newFileName = 'image-1';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $article->save();


    $_FILES = [
        'Article[file]' => [
            'name' => 'test-image-2.jpg',
            'type' => 'image/jpeg',
            'size' => 74463,
            'tmp_name' => __DIR__ . '/../data/test-image-2.jpg',
            'error' => 0,
        ],
    ];
    $recordOne = Article::findOne($article->id);

    $this->assertEquals($recordOne->img_src, 'image-1.jpg', 'assertEquals image-1.jpg');

    $recordOne->text = 'some updated text';
    $recordOne->newFileName = 'image-2';
    $recordOne->file = UploadedFile::getInstanceByName('Article[file]');
    $recordOne->save();

    $recordTwo = Article::findOne($article->id);

    $attrNameOne = $recordOne->nameOfAttributeStorage;
    $attrNameTwo = $recordTwo->nameOfAttributeStorage;

    $this->assertEquals($recordOne->id, $recordTwo->id);
    $this->assertEquals($recordTwo->img_src, 'image-2.jpg', 'assertEquals image-2.jpg');
  }

  public function testHendlersReducerIfNotFileSet()
  {
    $mock = Mockery::mock('tests\data\model\Article');
    $mock->shouldReceive('hendlersReducer')
         ->once();

    $this->assertEquals(false, $mock->hendlersReducer(true));
  }

  public function testHendlersReducerIfTargetWithoutPath()
  {
    $this->expectException(InvalidCallException::class);
    $article = new Article();
    $article->text = 'some text';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $behavior = $article->getBehavior('uploadFileBehavior');
    $behavior->targets[] = ['path' => null, 'hendler' => null];
    $article->save();
  }

  public function testHendlersReducerIfTargetWithoutHendler()
  {
    $this->expectException(InvalidCallException::class);
    $article = new Article();
    $article->text = 'some text';
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $behavior = $article->getBehavior('uploadFileBehavior');
    $behavior->targets = [];
    $behavior->targets[] = [
        'path' => function($attributes){
            return \Yii::getAlias('@uploadsPath');
        }, 
        'hendler' => null
    ];
    $article->save();
  }

  public function testGetPathAsString()
  {
    $b = \Yii::createObject([
        'class' => UploadFileBehavior::class,
        'targets' => ['data'],
    ]);
    $class = new \ReflectionClass(UploadFileBehavior::class);
    $method = $class->getMethod('getPath');
    $method->setAccessible(true);
    $r = $method->invokeArgs($b, array('@uploadsPath'));
    $this->assertSame(dirname(__DIR__) . '/data/uploads/', $r);
  }

  public function testSetHendlerLikeArrayConfigs()
  {
    $article = new Article();
    $article->detachBehaviors();
    $article->attachBehavior('myBehavior', [
        'class' => UploadFileBehavior::className(),
        'nameOfAttributeStorage' => 'img_src',
        'newFileName' => 'image-123',
        'targets' => [
            [
                'path' => '@uploadsPath',
                'hendler' => [
                    'type' => UploadFileBehavior::TYPE_IMAGE,
                    'config' => [
                        'size' => [
                            'width' => 400,
                            'height'=> 400*2/3
                        ],
                        'quality' => 80
                    ]
                ]
            ]
        ]
    ]);
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $article->text = 'some text';
    $res = $article->save();

    $this->assertSame($article->img_src, 'image-123.jpg');
    
    $this->assertTrue(is_file(\Yii::getAlias('@uploadsPath/image-123.jpg')));

  }

  public function testExeptionWhenFileTypeNotSupported()
  {
    $this->expectException(InvalidConfigException::class);
    $article = new Article();
    $article->detachBehaviors();
    $article->attachBehavior('myBehavior', [
        'class' => UploadFileBehavior::className(),
        'nameOfAttributeStorage' => 'img_src',
        'newFileName' => 'image-123',
        'targets' => [
            [
                'path' => '@uploadsPath',
                'hendler' => [
                    'type' => UploadFileBehavior::TYPE_FILE,
                    'config' => []
                ]
            ]
        ]
    ]);
    $article->file = UploadedFile::getInstanceByName('Article[file]');
    $article->text = 'some text';
    $res = $article->save();

  }

}