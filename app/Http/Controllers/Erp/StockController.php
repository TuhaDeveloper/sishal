<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductVariationStock;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function stocklist(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('stock report')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $categories = \App\Models\ProductServiceCategory::whereNull('parent_id')->where('status', 'active')->orderBy('name')->get();
        $subcategories = \App\Models\ProductServiceCategory::whereNotNull('parent_id')->where('status', 'active')->with('parent')->orderBy('name')->get();
        $brands = \App\Models\Brand::where('status', 'active')->orderBy('name')->get();
        $seasons = \App\Models\Season::where('status', 'active')->orderBy('name')->get();
        $genders = \App\Models\Gender::all();
        $variationValues = \App\Models\VariationAttributeValue::whereHas('variations')->orderBy('value')->get();
        $styles = Product::whereNotNull('style_number')
            ->where('style_number', '!=', '')
            ->selectRaw('TRIM(style_number) as style_no')
            ->distinct()
            ->orderBy('style_no')
            ->pluck('style_no')
            ->unique()
            ->filter()
            ->values();

        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;
        $selectedWarehouseId = $request->warehouse_id;
        $selectedVariationValueId = $request->variation_value_id;

        $query = $this->getStockQuery($request);
        $productStocks = $query->paginate((int) $request->get('per_page', 20))->appends($request->except('page'));

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // We build the query based on Variations if it exists, otherwise the Product itself
        // But the user wants a row for EACH variation + location.
        // This means we should iterate over ProductVariationStock and handle simple products separately.

        $query = $this->getStockQuery($request);
        $productStocks = $query->paginate((int) $request->get('per_page', 20))->appends($request->except('page'));

        // Load all movement relations for the paginated items
        $productStocks->load([
            'purchaseItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('purchase');
            },
            'purchaseReturnItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('purchaseReturn');
            },
            'saleItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('pos');
            },
            'invoiceItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
            },
            'orderItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('order');
            },
            'saleReturnItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('saleReturn');
            },
            'orderReturnItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
                $q->with('orderReturn');
            },
            'stockAdjustmentItems' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
            },
            'stockTransfers' => function ($q) use ($startDate, $endDate) {
                if ($startDate)
                    $q->whereDate('created_at', '>=', $startDate);
                if ($endDate)
                    $q->whereDate('created_at', '<=', $endDate);
            },
            'branchStock.branch',
            'warehouseStock.warehouse',
            'variationStocks.branch',
            'variationStocks.warehouse'
        ]);

        $totalStockQty = 0;
        $totalStockValue = 0;
        $totalStockRevenue = 0;

        // Simplified aggregate logic for totals
        $totals = \DB::table('products')
            ->selectRaw('SUM(cost) as total_value')
            ->first();

        $isDateFiltered = ($startDate || $endDate);

        if ($request->ajax()) {
            return view('erp.productStock.partials.table', compact(
                'productStocks',
                'totalStockQty',
                'totalStockValue',
                'totalStockRevenue',
                'isDateFiltered',
                'selectedBranchId',
                'selectedWarehouseId'
            ))->render();
        }

        return view('erp.productStock.productStockList', compact(
            'productStocks',
            'branches',
            'warehouses',
            'categories',
            'subcategories',
            'brands',
            'seasons',
            'genders',
            'variationValues',
            'styles',
            'totalStockQty',
            'totalStockValue',
            'totalStockRevenue',
            'selectedBranchId',
            'selectedWarehouseId',
            'restrictedBranchId',
            'isDateFiltered'
        ));
    }



    public function exportStockExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);
        $products = $this->getStockQuery($request)->paginate($perPage, ['*'], 'page', $page)->items();
        $startDate = $request->start_date;
        $endDate = $request->end_date;



        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Product Name',
            'Style #',
            'Color',
            'Size',
            'Outlet',
            'Opening',
            'P-Qnt',
            'PR-Qnt',
            'Net-P',
            'S-Qnt',
            'SR-Qnt',
            'Net-S',
            'Adjust',
            'Exc-To',
            'Exc-Fr',
            'Tr-Fr',
            'Tr-To',
            'STOCK',
            'Cost Value',
            'Sale Value',
            'Actual Revenue'
        ];

        foreach ($headers as $key => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($key + 1);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF111827');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        }
        $row = 2;
        $t_op = 0;
        $t_p = 0;
        $t_pr = 0;
        $t_np = 0;
        $t_s = 0;
        $t_sr = 0;
        $t_ns = 0;
        $t_adj = 0;
        $t_et = 0;
        $t_ef = 0;
        $t_tf = 0;
        $t_tt = 0;
        $t_stk = 0;
        $t_cost = 0;
        $t_sale = 0;
        $t_rev = 0;

        foreach ($products as $prod) {
            $productLocations = [];
            foreach ($prod->branchStock as $bs) {
                $productLocations['branch_' . $bs->branch_id] = ['type' => 'branch', 'id' => $bs->branch_id, 'name' => $bs->branch->name ?? 'Unknown'];
            }
            foreach ($prod->warehouseStock as $ws) {
                $productLocations['warehouse_' . $ws->warehouse_id] = ['type' => 'warehouse', 'id' => $ws->warehouse_id, 'name' => $ws->warehouse->name ?? 'Unknown'];
            }
            foreach ($prod->variationStocks as $vs) {
                $lkey = $vs->branch_id ? 'branch_' . $vs->branch_id : 'warehouse_' . $vs->warehouse_id;
                if (!isset($productLocations[$lkey])) {
                    $productLocations[$lkey] = ['type' => $vs->branch_id ? 'branch' : 'warehouse', 'id' => $vs->branch_id ?: $vs->warehouse_id, 'name' => ($vs->branch->name ?? $vs->warehouse->name) ?? 'Unknown'];
                }
            }

            // PRE-AGGREGATE MOVEMENTS FOR O(1) LOOKUP PERFORMANCE
            if (!isset($prod->agg)) {
                $agg = ['p' => [], 'pr' => [], 's' => [], 'sr' => [], 'adj' => [], 'et' => [], 'ef' => [], 'tf' => [], 'tt' => [], 'rev' => []];

                foreach ($prod->purchaseItems as $m) {
                    if ($m->purchase) {
                        $k = ($m->variation_id ?: 0) . '_' . $m->purchase->ship_location_type . '_' . $m->purchase->location_id;
                        $agg['p'][$k] = ($agg['p'][$k] ?? 0) + $m->quantity;
                    }
                }
                foreach ($prod->purchaseReturnItems as $m) {
                    $k = ($m->variation_id ?: 0) . '_' . $m->return_from_type . '_' . $m->return_from_id;
                    $agg['pr'][$k] = ($agg['pr'][$k] ?? 0) + $m->returned_qty;
                }
                foreach ($prod->saleItems as $m) {
                    if ($m->pos) {
                        $k = ($m->variation_id ?: 0) . '_branch_' . $m->pos->branch_id;
                        if ($m->pos->sale_type != 'exchange')
                            $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                        else
                            $agg['et'][$k] = ($agg['et'][$k] ?? 0) + $m->quantity;
                        $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                    }
                }
                foreach ($prod->invoiceItems as $m) {
                    $k = ($m->variation_id ?: 0) . '_warehouse_0';
                    $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                    $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                }
                foreach ($prod->orderItems as $m) {
                    if ($m->order) {
                        $k = ($m->variation_id ?: 0) . '_branch_' . $m->order->branch_id;
                        $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                        $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                    }
                }
                foreach ($prod->saleReturnItems as $m) {
                    if ($m->saleReturn) {
                        $k = ($m->variation_id ?: 0) . '_' . $m->saleReturn->return_to_type . '_' . $m->saleReturn->return_to_id;
                        if ($m->saleReturn->refund_type != 'exchange')
                            $agg['sr'][$k] = ($agg['sr'][$k] ?? 0) + $m->returned_qty;
                        else
                            $agg['ef'][$k] = ($agg['ef'][$k] ?? 0) + $m->returned_qty;
                        $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) - $m->total_price;
                    }
                }
                foreach ($prod->orderReturnItems as $m) {
                    if ($m->orderReturn) {
                        $k = ($m->variation_id ?: 0) . '_' . $m->orderReturn->return_to_type . '_' . $m->orderReturn->return_to_id;
                        $agg['sr'][$k] = ($agg['sr'][$k] ?? 0) + $m->returned_qty;
                        $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) - $m->total_price;
                    }
                }
                foreach ($prod->stockAdjustmentItems as $m) {
                    if ($m->adjustment) {
                        $ltype_adj = $m->adjustment->branch_id ? 'branch' : 'warehouse';
                        $lid_adj = $m->adjustment->branch_id ?: $m->adjustment->warehouse_id;
                        $k = ($m->variation_id ?: 0) . '_' . $ltype_adj . '_' . $lid_adj;
                        $agg['adj'][$k] = ($agg['adj'][$k] ?? 0) + ($m->new_quantity - $m->old_quantity);
                    }
                }
                foreach ($prod->stockTransfers as $m) {
                    if (in_array($m->status, ['approved', 'shipped', 'delivered'])) {
                        $k_from = ($m->variation_id ?: 0) . '_' . $m->from_type . '_' . $m->from_id;
                        $agg['tf'][$k_from] = ($agg['tf'][$k_from] ?? 0) + $m->quantity;
                    }
                    if ($m->status == 'delivered') {
                        $k_to = ($m->variation_id ?: 0) . '_' . $m->to_type . '_' . $m->to_id;
                        $agg['tt'][$k_to] = ($agg['tt'][$k_to] ?? 0) + $m->quantity;
                    }
                }
                $prod->agg = $agg;
            }

            $isProductSummary = ($request->get('group_by') === 'product');

            foreach ($productLocations as $loc) {
                $lid = $loc['id'];
                $ltype = $loc['type'];
                $vars = ($prod->has_variations && !$isProductSummary) ? $prod->variations : [null];

                foreach ($vars as $var) {
                    if ($isProductSummary && $prod->has_variations) {
                        $p_qnt = 0; $pr_qnt = 0; $s_qnt = 0; $sr_qnt = 0; $adjust = 0;
                        $exc_to = 0; $exc_from = 0; $tr_from = 0; $tr_to = 0;
                        $stock_qty = 0; $cost_val = 0; $sale_val = 0; $actual_revenue = 0;

                        foreach ($prod->variations as $v) {
                            $vid = $v->id;
                            $key = $vid . '_' . $ltype . '_' . $lid;
                            $wh_key = $vid . '_warehouse_0';

                            $p_qnt += $prod->agg['p'][$key] ?? 0;
                            $pr_qnt += $prod->agg['pr'][$key] ?? 0;

                            $v_s = $prod->agg['s'][$key] ?? 0;
                            if ($ltype == 'warehouse') $v_s += $prod->agg['s'][$wh_key] ?? 0;
                            $s_qnt += $v_s;

                            $sr_qnt += $prod->agg['sr'][$key] ?? 0;
                            $adjust += $prod->agg['adj'][$key] ?? 0;
                            $exc_to += $prod->agg['et'][$key] ?? 0;
                            $exc_from += $prod->agg['ef'][$key] ?? 0;
                            $tr_from += $prod->agg['tf'][$key] ?? 0;
                            $tr_to += $prod->agg['tt'][$key] ?? 0;

                            $v_stk = $v->stocks->where($ltype == 'branch' ? 'branch_id' : 'warehouse_id', $lid)->sum('quantity');
                            $stock_qty += $v_stk;
                            $v_cost = $v->cost ?: $prod->cost;
                            $v_price = $v->price ?: $prod->price;
                            $cost_val += ($v_stk * $v_cost);
                            $sale_val += ($v_stk * $v_price);

                            $v_rev = $prod->agg['rev'][$key] ?? 0;
                            if ($ltype == 'warehouse') $v_rev += $prod->agg['rev'][$wh_key] ?? 0;
                            $actual_revenue += $v_rev;
                        }

                        $inflows = $p_qnt + $sr_qnt + ($adjust > 0 ? $adjust : 0) + $tr_to + $exc_from;
                        $outflows = $s_qnt + $pr_qnt + ($adjust < 0 ? abs($adjust) : 0) + $tr_from + $exc_to;
                        $opening_stock = $stock_qty - ($inflows - $outflows);
                        $color = 'All';
                        $size = 'All';
                    } else {
                        $vid = $var ? $var->id : 0;
                        $key = $vid . '_' . $ltype . '_' . $lid;
                        $wh_key = $vid . '_warehouse_0';

                        $p_qnt = $prod->agg['p'][$key] ?? 0;
                        $pr_qnt = $prod->agg['pr'][$key] ?? 0;

                        $s_qnt = $prod->agg['s'][$key] ?? 0;
                        if ($ltype == 'warehouse')
                            $s_qnt += $prod->agg['s'][$wh_key] ?? 0;

                        $sr_qnt = $prod->agg['sr'][$key] ?? 0;
                        $adjust = $prod->agg['adj'][$key] ?? 0;
                        $exc_to = $prod->agg['et'][$key] ?? 0;
                        $exc_from = $prod->agg['ef'][$key] ?? 0;
                        $tr_from = $prod->agg['tf'][$key] ?? 0;
                        $tr_to = $prod->agg['tt'][$key] ?? 0;

                        $stock_qty = 0;
                        if ($prod->has_variations) {
                            $stock_qty = $var->stocks->where($ltype == 'branch' ? 'branch_id' : 'warehouse_id', $lid)->sum('quantity');
                        } else {
                            $stock_qty = ($ltype == 'branch' ? $prod->branchStock->where('branch_id', $lid)->sum('quantity') : $prod->warehouseStock->where('warehouse_id', $lid)->sum('quantity'));
                        }

                        $inflows = $p_qnt + $sr_qnt + ($adjust > 0 ? $adjust : 0) + $tr_to + $exc_from;
                        $outflows = $s_qnt + $pr_qnt + ($adjust < 0 ? abs($adjust) : 0) + $tr_from + $exc_to;
                        $opening_stock = $stock_qty - ($inflows - $outflows);

                        $color = '-';
                        $size = '-';
                        if ($var) {
                            foreach ($var->attributeValues as $av) {
                                $attr = strtolower($av->attribute->name ?? '');
                                if (str_contains($attr, 'color'))
                                    $color = $av->value;
                                elseif (str_contains($attr, 'size'))
                                    $size = $av->value;
                            }
                        }
                        $cost = $var ? ($var->cost ?: $prod->cost) : $prod->cost;
                        $price = $var ? ($var->price ?: $prod->price) : $prod->price;
                        $cost_val = $stock_qty * $cost;
                        $sale_val = $stock_qty * $price;

                        $actual_revenue = $prod->agg['rev'][$key] ?? 0;
                        if ($ltype == 'warehouse')
                            $actual_revenue += $prod->agg['rev'][$wh_key] ?? 0;
                    }

                    $sheet->setCellValue('A' . $row, $prod->name);
                    $sheet->setCellValue('B' . $row, $prod->style_number ?? $prod->sku);
                    $sheet->setCellValue('C' . $row, $color);
                    $sheet->setCellValue('D' . $row, $size);
                    $sheet->setCellValue('E' . $row, $loc['name']);
                    $sheet->setCellValue('F' . $row, $opening_stock);
                    $sheet->setCellValue('G' . $row, $p_qnt);
                    $sheet->setCellValue('H' . $row, $pr_qnt);
                    $sheet->setCellValue('I' . $row, $p_qnt - $pr_qnt);
                    $sheet->setCellValue('J' . $row, $s_qnt);
                    $sheet->setCellValue('K' . $row, $sr_qnt);
                    $sheet->setCellValue('L' . $row, $s_qnt - $sr_qnt);
                    $sheet->setCellValue('M' . $row, $adjust);
                    $sheet->setCellValue('N' . $row, $exc_to);
                    $sheet->setCellValue('O' . $row, $exc_from);
                    $sheet->setCellValue('P' . $row, $tr_from);
                    $sheet->setCellValue('Q' . $row, $tr_to);
                    $sheet->setCellValue('R' . $row, $stock_qty);
                    $sheet->setCellValue('S' . $row, $cost_val);
                    $sheet->setCellValue('T' . $row, $sale_val);
                    $sheet->setCellValue('U' . $row, $actual_revenue);
                    $t_op += $opening_stock;
                    $t_p += $p_qnt;
                    $t_pr += $pr_qnt;
                    $t_np += ($p_qnt - $pr_qnt);
                    $t_s += $s_qnt;
                    $t_sr += $sr_qnt;
                    $t_ns += ($s_qnt - $sr_qnt);
                    $t_adj += $adjust;
                    $t_et += $exc_to;
                    $t_ef += $exc_from;
                    $t_tf += $tr_from;
                    $t_tt += $tr_to;
                    $t_stk += $stock_qty;
                    $t_cost += $cost_val;
                    $t_sale += $sale_val;
                    $t_rev += $actual_revenue;

                    $row++;
                }
            }
        }

        // Total Row
        $sheet->setCellValue('A' . $row, 'TOTALS');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, $t_op);
        $sheet->setCellValue('G' . $row, $t_p);
        $sheet->setCellValue('H' . $row, $t_pr);
        $sheet->setCellValue('I' . $row, $t_np);
        $sheet->setCellValue('J' . $row, $t_s);
        $sheet->setCellValue('K' . $row, $t_sr);
        $sheet->setCellValue('L' . $row, $t_ns);
        $sheet->setCellValue('M' . $row, $t_adj);
        $sheet->setCellValue('N' . $row, $t_et);
        $sheet->setCellValue('O' . $row, $t_ef);
        $sheet->setCellValue('P' . $row, $t_tf);
        $sheet->setCellValue('Q' . $row, $t_tt);
        $sheet->setCellValue('R' . $row, $t_stk);
        $sheet->setCellValue('S' . $row, $t_cost);
        $sheet->setCellValue('T' . $row, $t_sale);
        $sheet->setCellValue('U' . $row, $t_rev);
        $sheet->getStyle('A' . $row . ':U' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':U' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'detailed_inventory_report_' . date('Y-m-d') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);
        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function exportStockPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);
        $products = $this->getStockQuery($request)->paginate($perPage, ['*'], 'page', $page)->items();
        $startDate = $request->start_date;
        $endDate = $request->end_date;



        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.productStock.stock-report-pdf', compact('products'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('detailed_inventory_report_' . date('Y-m-d') . '.pdf');
    }

    private function getStockQuery(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;
        $selectedWarehouseId = $request->warehouse_id;
        $selectedVariationValueId = $request->variation_value_id;

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($request->filled('filter_year')) {
            $year = $request->filter_year;
            $month = $request->filled('filter_month') ? str_pad($request->filter_month, 2, '0', STR_PAD_LEFT) : null;
            if ($month) {
                $startDate = "$year-$month-01";
                $endDate = date("Y-m-t", strtotime($startDate));
            } else {
                $startDate = "$year-01-01";
                $endDate = "$year-12-31";
            }
        }

        // Merge back into request so relations can use them
        if ($startDate && $endDate) {
            $request->merge(['start_date' => $startDate, 'end_date' => $endDate]);
        }

        $query = Product::with([
            'category:id,name',
            'brand:id,name',
            'season:id,name',
            'gender:id,name',
            'branchStock' => function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch)
                    $q->where('branch_id', $activeBranch);
                elseif ($selectedWarehouseId)
                    $q->whereRaw('1=0');
                $q->with('branch:id,name');
            },
            'warehouseStock' => function ($q) use ($selectedWarehouseId, $selectedBranchId, $restrictedBranchId) {
                if ($restrictedBranchId)
                    $q->whereRaw('1=0');
                elseif ($selectedWarehouseId)
                    $q->where('warehouse_id', $selectedWarehouseId);
                elseif ($selectedBranchId)
                    $q->whereRaw('1=0');
                $q->with('warehouse:id,name');
            },
            'variations' => function ($q) use ($selectedVariationValueId) {
                if ($selectedVariationValueId) {
                    $q->whereHas('attributeValues', function ($sq) use ($selectedVariationValueId) {
                        $sq->where('variation_attribute_values.id', $selectedVariationValueId);
                    });
                }
                $q->with('attributeValues');
            },
            'variations.stocks' => function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->where(function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        if ($activeBranch)
                            $sq->where('branch_id', $activeBranch);
                        if ($selectedWarehouseId) {
                            if ($activeBranch)
                                $sq->orWhere('warehouse_id', $selectedWarehouseId);
                            else
                                $sq->where('warehouse_id', $selectedWarehouseId);
                        }
                    });
                }
                $q->with(['branch:id,name', 'warehouse:id,name']);
            },
            'variationStocks' => function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->where(function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        if ($activeBranch)
                            $sq->where('branch_id', $activeBranch);
                        if ($selectedWarehouseId) {
                            if ($activeBranch)
                                $sq->orWhere('warehouse_id', $selectedWarehouseId);
                            else
                                $sq->where('warehouse_id', $selectedWarehouseId);
                        }
                    });
                }
                $q->with(['branch:id,name', 'warehouse:id,name']);
            },
            'purchaseItems' => function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId, $request, $selectedVariationValueId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);
                if (!$request->filled('start_date') && !$request->filled('end_date'))
                    $q->whereYear('created_at', date('Y'));

                if ($selectedVariationValueId) {
                    $q->whereHas('variation.attributeValues', function ($sq) use ($selectedVariationValueId) {
                        $sq->where('variation_attribute_values.id', $selectedVariationValueId);
                    });
                }

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->whereHas('purchase', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        $sq->where(function ($ssq) use ($activeBranch, $selectedWarehouseId) {
                            if ($activeBranch)
                                $ssq->where('ship_location_type', 'branch')->where('location_id', $activeBranch);
                            if ($selectedWarehouseId) {
                                if ($activeBranch)
                                    $ssq->orWhere(function ($sssq) use ($selectedWarehouseId) {
                                        $sssq->where('ship_location_type', 'warehouse')->where('location_id', $selectedWarehouseId); });
                                else
                                    $ssq->where('ship_location_type', 'warehouse')->where('location_id', $selectedWarehouseId);
                            }
                        });
                    });
                }
            },
            'saleItems' => function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId, $request, $selectedVariationValueId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);
                if (!$request->filled('start_date') && !$request->filled('end_date'))
                    $q->whereYear('created_at', date('Y'));

                if ($selectedVariationValueId) {
                    $q->whereHas('variation.attributeValues', function ($sq) use ($selectedVariationValueId) {
                        $sq->where('variation_attribute_values.id', $selectedVariationValueId);
                    });
                }

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch) {
                    $q->whereHas('pos', function ($sq) use ($activeBranch) {
                        $sq->where('branch_id', $activeBranch);
                    });
                } elseif ($selectedWarehouseId) {
                    $q->whereRaw('1=0'); // POS sales are typically branch-only
                }
            },
            'purchaseReturnItems' => function ($q) use ($request, $selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->where(function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        if ($activeBranch)
                            $sq->where('return_from_type', 'branch')->where('return_from_id', $activeBranch);
                        if ($selectedWarehouseId) {
                            if ($activeBranch)
                                $sq->orWhere(function ($ssq) use ($selectedWarehouseId) {
                                    $ssq->where('return_from_type', 'warehouse')->where('return_from_id', $selectedWarehouseId); });
                            else
                                $sq->where('return_from_type', 'warehouse')->where('return_from_id', $selectedWarehouseId);
                        }
                    });
                }
            },
            'saleReturnItems' => function ($q) use ($request, $selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->whereHas('saleReturn', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        $sq->where(function ($ssq) use ($activeBranch, $selectedWarehouseId) {
                            if ($activeBranch)
                                $ssq->where('return_to_type', 'branch')->where('return_to_id', $activeBranch);
                            if ($selectedWarehouseId) {
                                if ($activeBranch)
                                    $ssq->orWhere(function ($sssq) use ($selectedWarehouseId) {
                                        $sssq->where('return_to_type', 'warehouse')->where('return_to_id', $selectedWarehouseId); });
                                else
                                    $ssq->where('return_to_type', 'warehouse')->where('return_to_id', $selectedWarehouseId);
                            }
                        });
                    });
                }
            },
            'stockAdjustmentItems' => function ($q) use ($request, $selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->whereHas('adjustment', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        if ($activeBranch)
                            $sq->where('branch_id', $activeBranch);
                        if ($selectedWarehouseId) {
                            if ($activeBranch)
                                $sq->orWhere('warehouse_id', $selectedWarehouseId);
                            else
                                $sq->where('warehouse_id', $selectedWarehouseId);
                        }
                    });
                }
            },
            'stockTransfers' => function ($q) use ($request, $selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
                if ($request->filled('start_date'))
                    $q->whereDate('created_at', '>=', $request->start_date);
                if ($request->filled('end_date'))
                    $q->whereDate('created_at', '<=', $request->end_date);

                $activeBranch = $selectedBranchId ?: $restrictedBranchId;
                if ($activeBranch || $selectedWarehouseId) {
                    $q->where(function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        if ($activeBranch)
                            $sq->where('from_id', $activeBranch)->where('from_type', 'branch')->orWhere('to_id', $activeBranch)->where('to_type', 'branch');
                        if ($selectedWarehouseId) {
                            $sq->orWhere(function ($ssq) use ($selectedWarehouseId) {
                                $ssq->where('from_id', $selectedWarehouseId)->where('from_type', 'warehouse')->orWhere('to_id', $selectedWarehouseId)->where('to_type', 'warehouse');
                            });
                        }
                    });
                }
            }
        ]);


        if ($selectedVariationValueId) {
            $query->whereHas('variations.attributeValues', function ($q) use ($selectedVariationValueId) {
                $q->where('variation_attribute_values.id', $selectedVariationValueId);
            });
        }

        // Filter: Only show products that have been purchased at least once OR have current stock in selected locations
        $query->where(function ($q) use ($selectedBranchId, $selectedWarehouseId, $restrictedBranchId) {
            $activeBranch = $selectedBranchId ?: $restrictedBranchId;

            $q->whereHas('purchaseItems')
                ->orWhereHas('branchStock', function ($sq) use ($activeBranch) {
                    if ($activeBranch)
                        $sq->where('branch_id', $activeBranch);
                    $sq->where('quantity', '>', 0);
                })
                ->orWhereHas('warehouseStock', function ($sq) use ($selectedWarehouseId, $restrictedBranchId) {
                    if ($restrictedBranchId)
                        $sq->whereRaw('1=0');
                    elseif ($selectedWarehouseId)
                        $sq->where('warehouse_id', $selectedWarehouseId);
                    $sq->where('quantity', '>', 0);
                })
                ->orWhereHas('variationStocks', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                    $sq->where('quantity', '>', 0);
                    if ($activeBranch || $selectedWarehouseId) {
                        $sq->where(function ($ssq) use ($activeBranch, $selectedWarehouseId) {
                            if ($activeBranch)
                                $ssq->where('branch_id', $activeBranch);
                            if ($selectedWarehouseId) {
                                if ($activeBranch)
                                    $ssq->orWhere('warehouse_id', $selectedWarehouseId);
                                else
                                    $ssq->where('warehouse_id', $selectedWarehouseId);
                            }
                        });
                    }
                });
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%")->orWhere('style_number', 'like', "%$search%");
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('category_id', $request->subcategory_id);
        } elseif ($request->filled('category_id')) {
            $cat = \App\Models\ProductServiceCategory::find($request->category_id);
            if ($cat) {
                $catIds = $cat->getAllChildIds();
                $query->whereIn('category_id', $catIds);
            } else {
                $query->where('category_id', $request->category_id);
            }
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('season_id')) {
            $query->where('season_id', $request->season_id);
        }
        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->gender_id);
        }
        if ($request->filled('style_number')) {
            $styleNo = trim($request->style_number);
            $query->where(function($q) use ($styleNo) {
                $q->where('style_number', 'like', '%' . $styleNo . '%')
                  ->orWhere('sku', 'like', '%' . $styleNo . '%');
            });
        }

        if ($request->filled('stock_status') || $request->filled('low_stock')) {
            $stockStatus = $request->get('stock_status', $request->low_stock == '1' ? 'low_stock' : '');
            $activeBranch = $selectedBranchId ?: $restrictedBranchId;

            if ($stockStatus === 'low_stock') {
                $query->where(function ($q) use ($activeBranch, $selectedWarehouseId) {
                    $q->whereHas('variationStocks', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        $sq->where('quantity', '<', 10);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                        elseif ($selectedWarehouseId) $sq->where('warehouse_id', $selectedWarehouseId);
                    })->orWhereHas('branchStock', function ($sq) use ($activeBranch) {
                        $sq->where('quantity', '<', 10);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                    });
                });
            } elseif ($stockStatus === 'out_of_stock') {
                $query->where(function ($q) use ($activeBranch, $selectedWarehouseId) {
                    $q->whereHas('variationStocks', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        $sq->where('quantity', '<=', 0);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                        elseif ($selectedWarehouseId) $sq->where('warehouse_id', $selectedWarehouseId);
                    })->orWhereHas('branchStock', function ($sq) use ($activeBranch) {
                        $sq->where('quantity', '<=', 0);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                    });
                });
            } elseif ($stockStatus === 'in_stock') {
                $query->where(function ($q) use ($activeBranch, $selectedWarehouseId) {
                    $q->whereHas('variationStocks', function ($sq) use ($activeBranch, $selectedWarehouseId) {
                        $sq->where('quantity', '>', 0);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                        elseif ($selectedWarehouseId) $sq->where('warehouse_id', $selectedWarehouseId);
                    })->orWhereHas('branchStock', function ($sq) use ($activeBranch) {
                        $sq->where('quantity', '>', 0);
                        if ($activeBranch) $sq->where('branch_id', $activeBranch);
                    });
                });
            }
        }

        // Order by last purchase date or product creation date (new first)
        $query->addSelect([
            'last_purchase_date' => \App\Models\PurchaseItem::select('created_at')
                ->whereColumn('product_id', 'products.id')
                ->latest()
                ->take(1)
        ])->orderByRaw('COALESCE(last_purchase_date, products.created_at) DESC');

        return $query;
    }

    public function addStockToBranches(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branches' => 'required|array',
            'branches.*' => 'exists:branches,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:1',
        ]);

        $productId = $request->product_id;
        $branches = $request->branches;
        $quantities = $request->quantities;

        \Log::alert($request->all());

        foreach ($branches as $i => $branchId) {
            $quantity = $quantities[$i];
            $stock = \App\Models\BranchProductStock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();

            $oldQty = $stock ? $stock->quantity : 0;
            $newQty = $oldQty + $quantity;

            if ($stock) {
                $stock->update([
                    'quantity' => $newQty,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            } else {
                \App\Models\BranchProductStock::create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'quantity' => $newQty,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }

            // Create Stock Adjustment Record
            $adjustment = \App\Models\StockAdjustment::create([
                'adjustment_number' => 'ADJ-' . time() . '-' . rand(100, 999),
                'date' => now(),
                'branch_id' => $branchId,
                'notes' => 'Quick add to branch',
                'created_by' => auth()->id() ?? 1,
            ]);

            \App\Models\StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $productId,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
            ]);
        }

        \App\Services\CacheService::clearProductCaches($productId);

        return response()->json(['success' => true, 'message' => 'Stock added to branches successfully.']);
    }

    public function addStockToWarehouses(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouses' => 'required|array',
            'warehouses.*' => 'exists:warehouses,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:1',
        ]);

        $productId = $request->product_id;
        $warehouses = $request->warehouses;
        $quantities = $request->quantities;

        foreach ($warehouses as $i => $warehouseId) {
            $quantity = $quantities[$i];
            $stock = \App\Models\WarehouseProductStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            $oldQty = $stock ? $stock->quantity : 0;
            $newQty = $oldQty + $quantity;

            if ($stock) {
                $stock->update([
                    'quantity' => $newQty,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            } else {
                \App\Models\WarehouseProductStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $newQty,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }

            // Create Stock Adjustment Record
            $adjustment = \App\Models\StockAdjustment::create([
                'adjustment_number' => 'ADJ-' . time() . '-' . rand(100, 999),
                'date' => now(),
                'warehouse_id' => $warehouseId,
                'notes' => 'Quick add to warehouse',
                'created_by' => auth()->id() ?? 1,
            ]);

            \App\Models\StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $productId,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
            ]);
        }

        \App\Services\CacheService::clearProductCaches($productId);

        return response()->json(['success' => true, 'message' => 'Stock added to warehouses successfully.']);
    }

    public function adjustStock(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'location_type' => 'required|in:branch,warehouse',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:stock_in,stock_out',
            'quantity' => 'required|numeric|min:1',
        ]);

        // Validate location ID based on location type
        if ($request->location_type == 'branch') {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
        } else {
            $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        }

        // If a variation_id is provided, adjust variation stock; otherwise fall back to product-level stock
        $isVariation = $request->filled('variation_id');
        $oldQty = 0;
        $newQty = 0;

        if ($request->location_type == 'branch') {
            if ($isVariation) {
                $stock = ProductVariationStock::where('variation_id', $request->variation_id)
                    ->where('branch_id', $request->branch_id)
                    ->whereNull('warehouse_id')
                    ->first();

                $oldQty = $stock ? $stock->quantity : 0;
                if ($stock) {
                    if ($request->type == 'stock_in') {
                        $stock->quantity += $request->quantity;
                    } else {
                        if ($stock->quantity >= $request->quantity) {
                            $stock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient variation stock'], 400);
                        }
                    }
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                    $newQty = $stock->quantity;
                } else {
                    if ($request->type == 'stock_in') {
                        ProductVariationStock::create([
                            'variation_id' => $request->variation_id,
                            'branch_id' => $request->branch_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                        $newQty = $request->quantity;
                    } else {
                        return response()->json(['success' => false, 'message' => 'No variation stock to decrement for this branch.'], 400);
                    }
                }
            } else {
                $branchStock = BranchProductStock::where('branch_id', $request->branch_id)->where('product_id', $request->product_id)->first();
                $oldQty = $branchStock ? $branchStock->quantity : 0;
                if ($branchStock) {
                    if ($request->type == 'stock_in') {
                        $branchStock->quantity += $request->quantity;
                    } else {
                        if ($branchStock->quantity >= $request->quantity) {
                            $branchStock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
                        }
                    }
                    $branchStock->save();
                    $newQty = $branchStock->quantity;
                } else {
                    if ($request->type == 'stock_in') {
                        BranchProductStock::create([
                            'branch_id' => $request->branch_id,
                            'product_id' => $request->product_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                        $newQty = $request->quantity;
                    } else {
                        return response()->json(['success' => false, 'message' => 'No stock found for this branch and product. Cannot stock out.'], 400);
                    }
                }
            }
        } else {
            if ($isVariation) {
                $stock = ProductVariationStock::where('variation_id', $request->variation_id)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->whereNull('branch_id')
                    ->first();

                $oldQty = $stock ? $stock->quantity : 0;
                if ($stock) {
                    if ($request->type == 'stock_in') {
                        $stock->quantity += $request->quantity;
                    } else {
                        if ($stock->quantity >= $request->quantity) {
                            $stock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient variation stock'], 400);
                        }
                    }
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                    $newQty = $stock->quantity;
                } else {
                    if ($request->type == 'stock_in') {
                        ProductVariationStock::create([
                            'variation_id' => $request->variation_id,
                            'warehouse_id' => $request->warehouse_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                        $newQty = $request->quantity;
                    } else {
                        return response()->json(['success' => false, 'message' => 'No variation stock to decrement for this warehouse.'], 400);
                    }
                }
            } else {
                $warehouseStock = WarehouseProductStock::where('warehouse_id', $request->warehouse_id)->where('product_id', $request->product_id)->first();
                $oldQty = $warehouseStock ? $warehouseStock->quantity : 0;
                if ($warehouseStock) {
                    if ($request->type == 'stock_in') {
                        $warehouseStock->quantity += $request->quantity;
                    } else {
                        if ($warehouseStock->quantity >= $request->quantity) {
                            $warehouseStock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
                        }
                    }
                    $warehouseStock->save();
                    $newQty = $warehouseStock->quantity;
                } else {
                    if ($request->type == 'stock_in') {
                        WarehouseProductStock::create([
                            'warehouse_id' => $request->warehouse_id,
                            'product_id' => $request->product_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                        $newQty = $request->quantity;
                    } else {
                        return response()->json(['success' => false, 'message' => 'No stock found for this warehouse and product. Cannot stock out.'], 400);
                    }
                }
            }
        }

        // Create Stock Adjustment Record
        $adjustment = \App\Models\StockAdjustment::create([
            'adjustment_number' => 'ADJ-' . time() . '-' . rand(100, 999),
            'date' => now(),
            'branch_id' => $request->location_type == 'branch' ? $request->branch_id : null,
            'warehouse_id' => $request->location_type == 'warehouse' ? $request->warehouse_id : null,
            'notes' => 'Manual adjustment: ' . ($request->type == 'stock_in' ? 'Stock In' : 'Stock Out'),
            'created_by' => auth()->id() ?? 1,
        ]);

        \App\Models\StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $request->product_id,
            'variation_id' => $request->variation_id ?? null,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
        ]);

        \App\Services\CacheService::clearProductCaches($request->product_id);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Stock adjusted successfully.']);
        }
        return redirect()->back()->with('success', 'Stock adjusted successfully.');
    }

    public function getCurrentStock(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_type' => 'required|in:branch,warehouse',
        ]);

        // Validate location ID based on location type
        if ($request->location_type == 'branch') {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
        } else {
            $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        }

        $productId = $request->product_id;
        $variationId = $request->variation_id ?? null;
        $locationType = $request->location_type;
        $quantity = 0;

        if ($variationId) {
            // Get variation stock
            if ($locationType == 'branch') {
                $stock = ProductVariationStock::where('variation_id', $variationId)
                    ->where('branch_id', $request->branch_id)
                    ->whereNull('warehouse_id')
                    ->first();
            } else {
                $stock = ProductVariationStock::where('variation_id', $variationId)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->whereNull('branch_id')
                    ->first();
            }
            $quantity = $stock ? $stock->quantity : 0;
        } else {
            // Get product-level stock
            if ($locationType == 'branch') {
                $stock = BranchProductStock::where('product_id', $productId)
                    ->where('branch_id', $request->branch_id)
                    ->first();
            } else {
                $stock = WarehouseProductStock::where('product_id', $productId)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->first();
            }
            $quantity = $stock ? $stock->quantity : 0;
        }

        return response()->json([
            'success' => true,
            'quantity' => $quantity
        ]);
    }
    public function adjustmentCreate()
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            abort(403, 'Unauthorized action.');
        }

        $branches = Branch::all();
        $warehouses = Warehouse::all();

        return view('erp.productStock.createAdjustment', compact('branches', 'warehouses'));
    }

    public function storeAdjustment(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric',
        ]);

        \DB::beginTransaction();
        try {
            $branchId = $request->branch_id;
            $userId = auth()->id() ?? 1;

            // Create adjustment header
            $adjustment = \App\Models\StockAdjustment::create([
                'adjustment_number' => 'ADJ-' . time() . rand(10, 99),
                'date' => now(),
                'branch_id' => $branchId,
                'notes' => $request->note,
                'created_by' => $userId,
            ]);

            foreach ($request->items as $itemData) {
                $productId = $itemData['product_id'];
                $variationId = $itemData['variation_id'] ?? null;
                $newQty = $itemData['quantity'];

                $oldQty = 0;

                if ($variationId) {
                    $stock = ProductVariationStock::where('variation_id', $variationId)
                        ->where('branch_id', $branchId)
                        ->whereNull('warehouse_id')
                        ->first();

                    if ($stock) {
                        $oldQty = $stock->quantity;
                        $stock->quantity = $newQty;
                        $stock->updated_by = $userId;
                        $stock->last_updated_at = now();
                        $stock->save();
                    } else {
                        ProductVariationStock::create([
                            'variation_id' => $variationId,
                            'branch_id' => $branchId,
                            'quantity' => $newQty,
                            'updated_by' => $userId,
                            'last_updated_at' => now(),
                        ]);
                    }
                } else {
                    $stock = BranchProductStock::where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->first();

                    if ($stock) {
                        $oldQty = $stock->quantity;
                        $stock->quantity = $newQty;
                        $stock->updated_by = $userId;
                        $stock->last_updated_at = now();
                        $stock->save();
                    } else {
                        BranchProductStock::create([
                            'product_id' => $productId,
                            'branch_id' => $branchId,
                            'quantity' => $newQty,
                            'updated_by' => $userId,
                            'last_updated_at' => now(),
                        ]);
                    }
                }

                // Record item
                $adjustment->items()->create([
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty,
                ]);
            }

            foreach ($request->items as $itemData) {
                \App\Services\CacheService::clearProductCaches($itemData['product_id']);
            }

            \DB::commit();
            return redirect()->route('stock.adjustment.list')->with('success', 'Stock adjusted successfully and record saved!');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Stock Adjustment Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Adjustment failed: ' . $e->getMessage());
        }
    }

    public function adjustmentList(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }

        $query = \App\Models\StockAdjustmentItem::with(['adjustment.branch', 'adjustment.creator', 'product.category', 'product.brand', 'product.season', 'product.gender', 'variation']);

        // Reports Filter logic (applied to parent adjustment)
        $reportType = $request->get('report_type', 'yearly');
        $startDate = null;
        $endDate = null;
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('n'));
            $year = $request->get('year', date('Y'));
            $query->whereHas('adjustment', function ($q) use ($month, $year) {
                $q->whereMonth('date', $month)->whereYear('date', $year);
            });
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $query->whereHas('adjustment', function ($q) use ($year) {
                $q->whereYear('date', $year);
            });
        } else {
            $startDate = $request->filled('start_date') ? $request->start_date : \Carbon\Carbon::today()->toDateString();
            $endDate = $request->filled('end_date') ? $request->end_date : \Carbon\Carbon::today()->toDateString();
            
            $query->whereHas('adjustment', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        }

        // Parent Adjustment Filters
        if ($request->filled('adjustment_number')) {
            $query->whereHas('adjustment', function ($q) use ($request) {
                $q->where('adjustment_number', 'like', '%' . $request->adjustment_number . '%');
            });
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;

        if ($selectedBranchId) {
            $query->whereHas('adjustment', function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            });
        }

        // Item/Product Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('style_number', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('style_number')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('style_number', 'like', '%' . $request->style_number . '%')
                    ->orWhere('sku', 'like', '%' . $request->style_number . '%');
            });
        }
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        if ($request->filled('brand_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }
        if ($request->filled('season_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('season_id', $request->season_id);
            });
        }
        if ($request->filled('gender_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('gender_id', $request->gender_id);
            });
        }

        $perPage = (int) $request->get('per_page', 50);
        $adjustments = $query->latest()->paginate($perPage)->appends($request->except('page'));

        if ($request->ajax()) {
            return view('erp.productStock.components.adjustmentTable', compact('adjustments'))->render();
        }

        $branches = Branch::all();
        $products = Product::orderBy('name')->get();
        $categories = \App\Models\ProductServiceCategory::whereNull('parent_id')->get();
        $brands = \App\Models\Brand::all();
        $seasons = \App\Models\Season::all();
        $genders = \App\Models\Gender::all();

        return view('erp.productStock.adjustmentList', compact(
            'adjustments',
            'branches',
            'products',
            'categories',
            'brands',
            'seasons',
            'genders',
            'reportType',
            'startDate',
            'endDate'
        ));
    }

    public function exportAdjustmentExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }
        $items = $this->getAdjustmentQuery($request)->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Serial No', 'Invoice', 'Date', 'Location', 'Category', 'Brand', 'Season', 'Gender', 'Product Name', 'Style Number', 'Old Qty', 'New Qty', 'Diff', 'Adjusted By'];
        foreach ($headers as $key => $header) {
            $sheet->setCellValue(chr(65 + $key) . '1', $header);
        }

        $row = 2;
        foreach ($items as $index => $item) {
            $location = '-';
            if ($item->adjustment?->branch) {
                $location = $item->adjustment->branch->name;
            } elseif ($item->adjustment?->warehouse) {
                $location = $item->adjustment->warehouse->name;
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->adjustment->adjustment_number ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->adjustment?->date ? $item->adjustment->date->format('d/m/Y') : '-');
            $sheet->setCellValue('D' . $row, $location);
            $sheet->setCellValue('E' . $row, $item->product?->category?->name ?? '-');
            $sheet->setCellValue('F' . $row, $item->product?->brand?->name ?? '-');
            $sheet->setCellValue('G' . $row, $item->product?->season?->name ?? '-');
            $sheet->setCellValue('H' . $row, $item->product?->gender?->name ?? '-');
            $sheet->setCellValue('I' . $row, $item->product?->name ?? 'Deleted Product');
            $sheet->setCellValue('J' . $row, $item->product?->style_number ?? '-');
            $sheet->setCellValue('K' . $row, $item->old_quantity);
            $sheet->setCellValue('L' . $row, $item->new_quantity);
            $sheet->setCellValue('M' . $row, $item->new_quantity - $item->old_quantity);
            $sheet->setCellValue('N' . $row, $item->adjustment?->creator?->name ?? 'Admin');
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'stock_adjustments_' . date('Y-m-d') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function exportAdjustmentPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view stock')) {
            abort(403, 'Unauthorized action.');
        }
        $adjustments = $this->getAdjustmentQuery($request)->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.productStock.adjustment-report-pdf', compact('adjustments'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('stock_adjustments_' . date('Y-m-d') . '.pdf');
    }

    public function updateOpeningStock(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage opening stock')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action. You need "manage opening stock" permission.'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_type' => 'required|in:branch,warehouse',
            'location_id' => 'required',
            'opening_stock' => 'required|numeric|min:0',
        ]);

        $productId = $request->product_id;
        $variationId = $request->filled('variation_id') ? (int) $request->variation_id : null;
        if ($variationId === 0)
            $variationId = null; // Normalize 0 to null for simple products
        $locationType = $request->location_type;
        $locationId = $request->location_id;
        $newOpeningStock = $request->opening_stock;

        // 1. Find the stock record
        $stock = null;
        if ($variationId) {
            $stock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                ->where($locationType == 'branch' ? 'branch_id' : 'warehouse_id', $locationId)
                ->first();
        } else {
            $model = $locationType == 'branch' ? \App\Models\BranchProductStock::class : \App\Models\WarehouseProductStock::class;
            $stock = $model::where('product_id', $productId)
                ->where($locationType == 'branch' ? 'branch_id' : 'warehouse_id', $locationId)
                ->first();
        }

        $currentCalculatedOpening = (float) $request->current_opening;
        $diff = (float) $newOpeningStock - $currentCalculatedOpening;
        $currentStock = $stock ? $stock->quantity : 0;
        $newQuantity = $currentStock + $diff;

        if (!$stock) {
            $model = $variationId ? \App\Models\ProductVariationStock::class : ($locationType == 'branch' ? \App\Models\BranchProductStock::class : \App\Models\WarehouseProductStock::class);
            $stockData = [
                $locationType == 'branch' ? 'branch_id' : 'warehouse_id' => $locationId,
                'quantity' => $newQuantity,
                'opening_stock' => $newOpeningStock,
                'updated_by' => auth()->id() ?? 1,
                'last_updated_at' => now(),
            ];
            if ($variationId)
                $stockData['variation_id'] = $variationId;
            else
                $stockData['product_id'] = $productId;

            $stock = $model::create($stockData);
        } else {
            // Adjust current quantity and set the new opening stock baseline
            $stock->quantity = $newQuantity;
            $stock->opening_stock = $newOpeningStock;
            $stock->updated_by = auth()->id() ?? 1;
            $stock->last_updated_at = now();
            $stock->save();
        }

        \App\Services\CacheService::clearProductCaches($productId);

        return response()->json([
            'success' => true,
            'message' => 'Opening stock updated successfully.',
            'new_quantity' => $stock->quantity,
            'new_opening_stock' => $stock->opening_stock
        ]);
    }

    public function deleteAdjustment(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('adjust stock')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $adjustment = \App\Models\StockAdjustment::with('items')->findOrFail($id);

        \DB::beginTransaction();
        try {
            foreach ($adjustment->items as $item) {
                // Calculate the difference this adjustment applied
                $diff = $item->new_quantity - $item->old_quantity;

                if ($item->variation_id) {
                    // --- Variation stock ---
                    if ($adjustment->branch_id) {
                        $stock = ProductVariationStock::where('variation_id', $item->variation_id)
                            ->where('branch_id', $adjustment->branch_id)
                            ->whereNull('warehouse_id')
                            ->first();
                    } else {
                        $stock = ProductVariationStock::where('variation_id', $item->variation_id)
                            ->where('warehouse_id', $adjustment->warehouse_id)
                            ->whereNull('branch_id')
                            ->first();
                    }

                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $diff);
                        $stock->updated_by = auth()->id() ?? 1;
                        $stock->last_updated_at = now();
                        $stock->save();
                    }
                } else {
                    // --- Product-level stock ---
                    if ($adjustment->branch_id) {
                        $stock = BranchProductStock::where('product_id', $item->product_id)
                            ->where('branch_id', $adjustment->branch_id)
                            ->first();
                    } else {
                        $stock = WarehouseProductStock::where('product_id', $item->product_id)
                            ->where('warehouse_id', $adjustment->warehouse_id)
                            ->first();
                    }

                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $diff);
                        $stock->updated_by = auth()->id() ?? 1;
                        $stock->last_updated_at = now();
                        $stock->save();
                    }
                }

                // Clear product cache
                \App\Services\CacheService::clearProductCaches($item->product_id);
            }

            // Delete items then the parent adjustment record
            $adjustment->items()->delete();
            $adjustment->delete();

            \DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Adjustment deleted and stock reversed successfully.']);
            }
            return redirect()->route('stock.adjustment.list')->with('success', 'Adjustment deleted and stock reversed successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Stock Adjustment Delete Failed: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete adjustment: ' . $e->getMessage());
        }
    }

    private function getAdjustmentQuery(Request $request)
    {
        $query = \App\Models\StockAdjustmentItem::with(['adjustment.branch', 'adjustment.creator', 'product.category', 'product.brand', 'product.season', 'product.gender', 'variation']);

        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('n'));
            $year = $request->get('year', date('Y'));
            $query->whereHas('adjustment', function ($q) use ($month, $year) {
                $q->whereMonth('date', $month)->whereYear('date', $year);
            });
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $query->whereHas('adjustment', function ($q) use ($year) {
                $q->whereYear('date', $year);
            });
        } else {
            $startDate = $request->filled('start_date') ? $request->start_date : \Carbon\Carbon::today()->toDateString();
            $endDate = $request->filled('end_date') ? $request->end_date : \Carbon\Carbon::today()->toDateString();
            
            $query->whereHas('adjustment', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        }

        if ($request->filled('adjustment_number')) {
            $query->whereHas('adjustment', function ($q) use ($request) {
                $q->where('adjustment_number', 'like', '%' . $request->adjustment_number . '%');
            });
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('adjustment', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('style_number')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('style_number', 'like', '%' . $request->style_number . '%');
            });
        }
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        return $query->latest();
    }
}
