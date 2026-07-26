<?php

namespace App\Support;

use PharData;

class EmployeePinDocx
{
    public static function build(iterable $records, string $outputPath): void
    {
        $xml = static fn ($value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cell = static function ($value, int $width, bool $bold = false, string $fill = '') use ($xml): string {
            $shade = $fill !== '' ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/>' : '';
            $run = $bold ? '<w:rPr><w:b/><w:color w:val="'.($fill !== '' ? 'FFFFFF' : '0F172A').'"/></w:rPr>' : '';
            return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.$shade
                .'<w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr>'
                .'<w:r>'.$run.'<w:t xml:space="preserve">'.$xml($value).'</w:t></w:r></w:p></w:tc>';
        };

        $rows = '<w:tr><w:trPr><w:tblHeader/></w:trPr>'
            .$cell('No.', 700, true, '047857')
            .$cell('Employee Name', 6000, true, '047857')
            .$cell('Department', 4000, true, '047857')
            .$cell('Temporary PIN / Status', 3700, true, '047857').'</w:tr>';
        $count = 0;
        foreach ($records as $record) {
            $count++;
            $rows .= '<w:tr>'
                .$cell($count, 700)
                .$cell($record['name'] ?? '', 6000)
                .$cell($record['department'] ?? '', 4000)
                .$cell($record['temporary_pin'] ?: 'Already activated', 3700)
                .'</w:tr>';
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:pPr><w:spacing w:after="80"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="34"/><w:color w:val="064E3B"/></w:rPr>'
            .'<w:t>Northeastern College Employee Temporary PIN List</w:t></w:r></w:p>'
            .'<w:p><w:pPr><w:spacing w:after="180"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/><w:color w:val="64748B"/></w:rPr>'
            .'<w:t>All NC employees and teachers | Total records: '.$count.' | Generated: '.$xml(now('Asia/Manila')->format('F j, Y g:i A')).'</w:t></w:r></w:p>'
            .'<w:tbl><w:tblPr><w:tblW w:w="14400" w:type="dxa"/><w:tblLayout w:type="fixed"/>'
            .'<w:tblBorders><w:top w:val="single" w:sz="6" w:color="94A3B8"/><w:left w:val="single" w:sz="6" w:color="94A3B8"/>'
            .'<w:bottom w:val="single" w:sz="6" w:color="94A3B8"/><w:right w:val="single" w:sz="6" w:color="94A3B8"/>'
            .'<w:insideH w:val="single" w:sz="4" w:color="CBD5E1"/><w:insideV w:val="single" w:sz="4" w:color="CBD5E1"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="90" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid><w:gridCol w:w="700"/><w:gridCol w:w="6000"/><w:gridCol w:w="4000"/><w:gridCol w:w="3700"/></w:tblGrid>'
            .$rows.'</w:tbl>'
            .'<w:p><w:pPr><w:spacing w:before="140"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="16"/><w:color w:val="64748B"/></w:rPr>'
            .'<w:t>Confidential: keep this list secure and destroy printed copies when no longer required.</w:t></w:r></w:p>'
            .'<w:sectPr><w:pgSz w:w="15840" w:h="12240" w:orient="landscape"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>'
            .'</w:body></w:document>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => $document,
            'word/_rels/document.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'word/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="18"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="220" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults></w:styles>',
        ];

        $zipPath = preg_replace('/\.docx$/i', '.zip', $outputPath);
        @unlink($zipPath);
        @unlink($outputPath);
        $archive = new PharData($zipPath);
        foreach ($files as $path => $contents) {
            $archive->addFromString($path, $contents);
        }
        unset($archive);
        rename($zipPath, $outputPath);
    }
}
