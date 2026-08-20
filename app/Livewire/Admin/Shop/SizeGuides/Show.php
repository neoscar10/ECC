<?php

namespace App\Livewire\Admin\Shop\SizeGuides;

use Livewire\Component;
use App\Models\Shop\ShopSizeGuide;

class Show extends Component
{
    public ShopSizeGuide $guide;

    // Configuration states
    public $name = '';
    public $description = '';
    public $how_to_measure_text = '';
    
    // Unified table array
    public $tables = [];

    public function mount(ShopSizeGuide $guide)
    {
        $this->guide = $guide;
        $this->name = $guide->name;
        $this->description = $guide->description;
        $this->how_to_measure_text = $guide->how_to_measure_text;

        $defaultTable = [
            [
                'title' => '',
                'unit' => 'cm',
                'columns' => ['Label', 'Value'],
                'rows' => [
                    [
                        'label' => '',
                        'values' => [
                            'cm' => ['', ''],
                            'inch' => ['', '']
                        ]
                    ]
                ]
            ]
        ];

        if ($guide->table_data) {
            $data = $guide->table_data;
            
            // Check if it is the new unified format
            if (isset($data[0]['rows'][0]['values'])) {
                $this->tables = $data;
            } else if (isset($data['cm']) || isset($data['inch'])) {
                $cm = $data['cm'] ?? [];
                $inch = $data['inch'] ?? [];
                
                $merged = [];
                foreach ($cm as $tIndex => $tCm) {
                    $tInch = $inch[$tIndex] ?? null;
                    
                    $columns = $tCm['columns'] ?? ['Label', 'Value'];
                    $rows = [];
                    foreach ($tCm['rows'] ?? [] as $rIndex => $rCm) {
                        $label = $rCm[0] ?? '';
                        
                        $cmVals = $rCm;
                        $cmVals[0] = ''; // clear label slot
                        
                        $inchVals = [];
                        if ($tInch && isset($tInch['rows'][$rIndex])) {
                            $inchVals = $tInch['rows'][$rIndex];
                            $inchVals[0] = '';
                        } else {
                            $inchVals = array_fill(0, count($columns), '');
                        }
                        
                        $rows[] = [
                            'label' => $label,
                            'values' => [
                                'cm' => $cmVals,
                                'inch' => $inchVals
                            ]
                        ];
                    }
                    
                    $merged[] = [
                        'title' => $tCm['title'] ?? '',
                        'unit' => 'cm',
                        'columns' => $columns,
                        'rows' => $rows
                    ];
                }
                $this->tables = $merged;
            } else {
                // Legacy flat format
                if (isset($data['columns'])) {
                    $legacyTables = [
                        [
                            'title' => '',
                            'columns' => $data['columns'] ?? [],
                            'rows' => $data['rows'] ?? []
                        ]
                    ];
                } else {
                    $legacyTables = $data;
                }
                
                $merged = [];
                $multiplier = $guide->cm_to_inch_multiplier ?: 0.3937;
                foreach ($legacyTables as $table) {
                    $columns = $table['columns'] ?? ['Label', 'Value'];
                    $rows = [];
                    foreach ($table['rows'] ?? [] as $row) {
                        $label = $row[0] ?? '';
                        
                        $cmVals = $row;
                        $cmVals[0] = '';
                        
                        $inchVals = [];
                        foreach ($row as $cIndex => $val) {
                            if ($cIndex > 0 && is_numeric(trim($val))) {
                                $inchVals[] = round((float)trim($val) * $multiplier, 1);
                            } else {
                                $inchVals[] = $val;
                            }
                        }
                        $inchVals[0] = '';
                        
                        $rows[] = [
                            'label' => $label,
                            'values' => [
                                'cm' => $cmVals,
                                'inch' => $inchVals
                            ]
                        ];
                    }
                    
                    $merged[] = [
                        'title' => $table['title'] ?? '',
                        'unit' => 'cm',
                        'columns' => $columns,
                        'rows' => $rows
                    ];
                }
                $this->tables = $merged;
            }
        } else {
            $this->tables = $defaultTable;
        }
    }

    private function getDefaultTableStructure()
    {
        return [
            'title' => '',
            'unit' => 'cm',
            'columns' => ['Label', 'Value'],
            'rows' => [
                [
                    'label' => '',
                    'values' => [
                        'cm' => ['', ''],
                        'inch' => ['', '']
                    ]
                ]
            ]
        ];
    }

    public function addTable()
    {
        $this->tables[] = $this->getDefaultTableStructure();
    }

    public function removeTable($tableIndex)
    {
        if (count($this->tables) > 1) {
            unset($this->tables[$tableIndex]);
            $this->tables = array_values($this->tables);
        }
    }

    public function addColumn($tableIndex)
    {
        $this->tables[$tableIndex]['columns'][] = '';
        foreach ($this->tables[$tableIndex]['rows'] as &$row) {
            $row['values']['cm'][] = '';
            $row['values']['inch'][] = '';
        }
    }

    public function removeColumn($tableIndex, $colIndex)
    {
        if (count($this->tables[$tableIndex]['columns']) > 1) {
            unset($this->tables[$tableIndex]['columns'][$colIndex]);
            $this->tables[$tableIndex]['columns'] = array_values($this->tables[$tableIndex]['columns']);
            foreach ($this->tables[$tableIndex]['rows'] as &$row) {
                unset($row['values']['cm'][$colIndex]);
                $row['values']['cm'] = array_values($row['values']['cm']);
                unset($row['values']['inch'][$colIndex]);
                $row['values']['inch'] = array_values($row['values']['inch']);
            }
        }
    }

    public function addRow($tableIndex)
    {
        $colsCount = count($this->tables[$tableIndex]['columns']);
        $this->tables[$tableIndex]['rows'][] = [
            'label' => '',
            'values' => [
                'cm' => array_fill(0, $colsCount, ''),
                'inch' => array_fill(0, $colsCount, '')
            ]
        ];
    }

    public function removeRow($tableIndex, $rowIndex)
    {
        if (count($this->tables[$tableIndex]['rows']) > 1) {
            unset($this->tables[$tableIndex]['rows'][$rowIndex]);
            $this->tables[$tableIndex]['rows'] = array_values($this->tables[$tableIndex]['rows']);
        }
    }

    public function setUnitForTable($tableIndex, $newUnit)
    {
        $this->tables[$tableIndex]['unit'] = $newUnit;
    }

    public function saveGuide()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->guide->update([
            'name' => $this->name,
            'description' => $this->description,
            'how_to_measure_text' => $this->how_to_measure_text,
            'how_to_measure_image_path' => null, // clean legacy
            'table_data' => $this->tables
        ]);

        session()->flash('success', 'Size guide configurations saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.shop.size-guides.show')
            ->layout('layouts.admin');
    }
}
