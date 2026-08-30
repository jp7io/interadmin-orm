<?php

use Jp7\InterAdmin\RecordClassMap;

class LanguageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seeNumRecords(0, 'interadmin_teste_records');
        $this->seeNumRecords(0, 'interadmin_teste_en_records');

        RecordClassMap::getInstance()->clearCache();
    }

    public function testWithLanguageChecked()
    {
        $this->createI18nNewsType(['language' => 'S']);

        $news = Test_Noticia::build();
        $news->title = 'Doria eleito prefeito';
        $news->save();

        $records = Test_Noticia::all();
        $this->assertCount(1, $records);

        App::setLocale('en');

        $records = Test_Noticia::all();
        $this->assertCount(0, $records);

        App::setLocale('pt-BR');
    }

    public function testWithoutLanguageChecked()
    {
        $this->createI18nNewsType();

        $news = Test_Noticia::build();
        $news->title = 'Lula preso';
        $news->save();

        $recordsPt = Test_Noticia::all();

        App::setLocale('en');

        $recordsEn = Test_Noticia::all();
        $this->assertEquals($recordsPt, $recordsEn);

        App::setLocale('pt-BR');
    }
    
    public function testTypeName()
    {
        $newsType = $this->createI18nNewsType(['language' => 'S', 'name_en' => 'News']);

        $nomePtBr = $newsType->getName();

        $this->assertEquals($newsType->name, $nomePtBr);

        App::setLocale('en');

        $nomeEnUs = $newsType->getName();

        $this->assertEquals($newsType->name_en, $nomeEnUs);

        App::setLocale('pt-BR');
    }
}
