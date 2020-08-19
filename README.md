# Yii2 upload file behavior #

## About
Yii2 upload file behavior - simple wey to upload images and files to server. 
No need anymore wrote tonn of code in controller and else testing it by houers. As a result - saving time and labor costs for uploading files to the site.
Only needed upload extention from github and past some less code to model class (\yii\db\ActiveRecord) where needed hendler uploading files.
More on this below.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

First download extention. Run the command in the terminal:
```
composer require "coderius/yii2-upload-file-behavior"
```

or add in composer.json
```
"coderius/yii2-upload-file-behavior": "^1.0"
```
and run `composer update`

## Usage
This extention created for usage in \yii\db\ActiveRecord model classes.

Configyration behavior.

* __$nameOfAttributeFile__ = (string) default name 'file'. Virtual attribute for uploading file instance from file systrem.
* __$nameOfAttributeStorage__ = (string) default name 'face_img'. Attribute for saving path to uploaded file in db.
* __$newFileName__ = (string) name which is assigned to uploaded file
* __$directories__ = (array) configs to upload folder and upload hendlers.Сonsists of separate arrays.
    Each array contains settings for the path to the target folder and a handler for uploading files to this folder like 'path' and 'handler'
        __-'path'__ - contains path to target folder
        __-'hendler'__ - Processes the downloaded file and saves to the specified in param 'path' location.

This extention created for usage in \yii\db\ActiveRecord model classes.

- So, first in model class put namespace to yii2-upload-file-behavior. 
- Create public variable $file for loading file from filesystem.
- The database must have an attribute to store the file path. In example below it is 'img_src' attribute (marked like save in public function rules())
- Then past needed configs behaviors() method like in example.

__!Note.__ _Don't forget to include the dependency namespaces._

**Example**

```
    namespase your/models;

    use coderius\yii2UploadFileBehavior\UploadFileBehavior;
    use yii\imagine\Image;
    use Imagine\Image\Point;
    use Imagine\Image\Box;

    class YourModel extends \yii\db\ActiveRecord
    {
        public $file;

        //'img_src' - attribute to save path to file in db
        public function rules()
        {
            return [
                [['img_src'], 'safe'],
        }

        ...

            public function behaviors()
            {
                return [
                    //Another behaviors
                    //...

                    'uploadFileBehavior' => [
                        'class' => UploadFileBehavior::className(),
                        'nameOfAttributeStorage' => 'img_src',
                        'directories' => [
                            
                            [
                                'path' => function($attributes){
                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/big/');
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
                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/middle/');
                                },
                                'hendler' => function($fileTempName, $newFilePath){
                                    Image::thumbnail($fileTempName, 400, 400*2/3)
                                    ->save($newFilePath, ['quality' => 80]);
                                    sleep(1);
                                }
                            ],
                            [
                                'path' => function($attributes){
                                    return \Yii::getAlias('@portfoleoPhotosPath/' . $attributes['id'] . '/small/');
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

        ...
    }    

```


Additional actions:
-------------------------------------
1. Create aliases to target folders for saving uploaded files.
2. Create target folders in 'frontend/web' dirrectory like example.
3. Don't forget create vertual attribute. If it named like '$file', then no need set config to 'nameOfAttributeFile'(default = (string)'file').

## Testing

Run tests in extention folder.

```bash
$ ./vendor/bin/phpunit
```

Note! 
For running all tests needed upload all dependencies by composer. If tested single extention, then run command from root directory where located extention:
```
composer update
```

When all dependencies downloaded run all tests in terminal from root folder:
```
./vendor/bin/phpunit tests
```
Or for only unit:
```
./vendor/bin/phpunit --testsuite Unit
```

If extention tested in app, then set correct path to phpunit and run some commands.

## Credits

- [Sergio Coderius](https://github.com/coderius)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.