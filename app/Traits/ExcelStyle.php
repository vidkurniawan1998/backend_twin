<?php


namespace App\Traits;


trait ExcelStyle
{
    public function border()
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    public function borderBottom() {
        return [
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    public function horizontalCenter()
    {
        return [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];
    }

    public function horizontalRight()
    {
        return [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ]
        ];
    }

    public function verticalCenter()
    {
        return [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
    }

    /**
     * Filling color to excel
     * @param string $color
     * @return array[]
     */
    public function cellColor(string $color)
    {
         return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => $color]
            ]
        ];
    }

    /**
     * @param Int $size
     * @return \Int[][]
     */
    public function fontSize(Int $size)
    {
        return [
            'font' => [
                'size' => $size
            ]
        ];
    }
}