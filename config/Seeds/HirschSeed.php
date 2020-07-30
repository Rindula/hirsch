<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Hirsch seed.
 */
class HirschSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => '1',
                'slug' => 'tagesessen',
                'name' => 'Tagesessen',
            ],
            [
                'id' => '2',
                'slug' => 'Schweizer-Wurstsalat-mit-Pommes',
                'name' => 'Schweizer Wurstsalat mit Pommes',
            ],
            [
                'id' => '3',
                'slug' => 'Bunte-Blattsalate-mit-Huhnerbrust',
                'name' => 'Bunte Blattsalate mit Hühnerbrust',
            ],
            [
                'id' => '4',
                'slug' => 'Bunte-Blattsalate-mit-gegrillten-Garnelen',
                'name' => 'Bunte Blattsalate mit gegrillten Garnelen',
            ],
            [
                'id' => '5',
                'slug' => 'Gebackener-Camembert-Preiselbeeren-und-Salat',
                'name' => 'Gebackener Camembert, Preiselbeeren und Salat',
            ],
            [
                'id' => '6',
                'slug' => 'Paniertes-Schweineschnitzel-Pommes-und-Salat',
                'name' => 'Paniertes Schweineschnitzel, Pommes und Salat',
            ],
            [
                'id' => '7',
                'slug' => 'Jagerschnitzel-Spatzle-und-Salat',
                'name' => 'Jägerschnitzel, Spätzle und Salat',
            ],
            [
                'id' => '8',
                'slug' => 'Zigeunerschnitzel-mit-Kroketten-und-Salat',
                'name' => 'Zigeunerschnitzel mit Kroketten und Salat',
            ],
            [
                'id' => '9',
                'slug' => 'Cordon-Bleu-mit-Pommes-und-Salat',
                'name' => 'Cordon Bleu mit Pommes und Salat',
            ],
            [
                'id' => '10',
                'slug' => 'Schweinefilet-in-Pilzrahmsauce-Spatzle-und-Salat',
                'name' => 'Schweinefilet in Pilzrahmsauce, Spätzle und Salat',
            ],
            [
                'id' => '11',
                'slug' => 'Schweinesteak-mit-Krauterbutter-Pommes-kleiner-Salat',
                'name' => 'Schweinesteak mit Kräuterbutter, Pommes kleiner Salat',
            ],
            [
                'id' => '12',
                'slug' => 'Kasespatzle-mit-buntem-Salat',
                'name' => 'Käsespätzle mit buntem Salat',
            ],
            [
                'id' => '13',
                'slug' => 'Salbeignocchi-mit-Grillgemuse-Parmesan-und-Ruccola',
                'name' => 'Salbeignocchi mit Grillgemüse, Parmesan und Ruccola',
            ],
        ];

        $table = $this->table('hirsch');
        $table->insert($data)->save();
    }
}
