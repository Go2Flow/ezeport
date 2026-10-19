<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportImportException;
use Go2Flow\Ezport\Process\Import\Xml\Split;
use PHPUnit\Framework\TestCase;

class XmlSplitTest extends TestCase
{
    public function test_unreadable_file_throws_an_import_exception(): void
    {
        $this->expectException(EzportImportException::class);
        $this->expectExceptionMessage('Could not open file /does/not/exist.xml');

        (new Split('/does/not/exist.xml', new Project))->batch('article');
    }

    private function file(string $xml): string
    {
        $path = tempnam(sys_get_temp_dir(), 'split').'.xml';
        file_put_contents($path, $xml);

        return $path;
    }

    private function numbers(string $xml, array $path): array
    {
        return (new Split($this->file($xml), new Project))->elements($path)
            ->map(fn ($item) => (string) simplexml_load_string($item->first())->Nr)
            ->all();
    }

    public function test_the_first_element_is_kept(): void
    {
        $xml = '<?xml version="1.0"?><HISEXPORT><Schnittstelle>5.4</Schnittstelle><Update>1</Update>'
            .'<Artikeldaten><Artikel><Nr>1</Nr></Artikel><Artikel><Nr>2</Nr></Artikel><Artikel><Nr>3</Nr></Artikel></Artikeldaten></HISEXPORT>';

        $this->assertSame(['1', '2', '3'], $this->numbers($xml, ['Artikeldaten', 'Artikel']));
        $this->assertSame(['1', '2', '3'], $this->numbers($xml, ['HISEXPORT', 'Artikeldaten', 'Artikel']));
        $this->assertSame(['1', '2', '3'], $this->numbers($xml, ['Artikel']));
    }

    public function test_elements_of_another_branch_are_left_out(): void
    {
        $xml = '<?xml version="1.0"?><HISEXPORT><Artikeldaten><Artikel><Nr>1</Nr></Artikel></Artikeldaten>'
            .'<Stammdaten><Farben><Farbe><Nr>7</Nr></Farbe><Farbe><Nr>8</Nr></Farbe></Farben></Stammdaten></HISEXPORT>';

        $this->assertSame(['7', '8'], $this->numbers($xml, ['HISEXPORT', 'Stammdaten', 'Farben', 'Farbe']));
    }
}
