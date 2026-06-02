<?php

namespace App\Http\Controllers;

use App\Services\SmartImportService;
use App\SmartImportBatch;
use App\SmartImportRow;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SmartImportController extends Controller
{
    public function index()
    {
        $batches = SmartImportBatch::where('user_id', auth()->id())
            ->latest('id')
            ->limit(10)
            ->get();

        return view('orders.smart_import.index', compact('batches'));
    }

    public function preview(Request $request, SmartImportService $service)
    {
        $file = $request->file('file');
        if (!$file) {
            Flash::error('Vui lòng chọn file Excel.');
            return redirect()->route('orders.smartImport.index');
        }

        $mimes = [
            'application/vnd.ms-excel',
            'text/xls',
            'text/xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (!in_array($file->getClientMimeType(), $mimes)) {
            Flash::error('File đã chọn phải là Excel.');
            return redirect()->route('orders.smartImport.index');
        }

        try {
            $batch = $service->createBatchFromFile($file, auth()->id());
        } catch (\Exception $e) {
            \Log::warning('Smart import preview failed', [
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'error' => $e->getMessage(),
            ]);
            Flash::error($e->getMessage());
            return redirect()->route('orders.smartImport.index');
        }

        return redirect()->route('orders.smartImport.show', $batch->id);
    }

    public function show(Request $request, SmartImportBatch $batch)
    {
        $this->authorizeBatch($batch);

        $status = $request->get('status');
        $rowsQuery = $batch->rows()->with('order')->orderBy('row_number');
        if (in_array($status, ['valid', 'error', 'imported'], true)) {
            $rowsQuery->where('status', $status);
        }
        $rows = $rowsQuery->paginate(50);
        $batch->loadCount(['rows']);

        return view('orders.smart_import.show', compact('batch', 'rows', 'status'));
    }

    public function updateRow(Request $request, SmartImportBatch $batch, SmartImportRow $row, SmartImportService $service)
    {
        $this->authorizeBatch($batch);
        if ((int)$row->smart_import_batch_id !== (int)$batch->id) {
            abort(404);
        }

        $service->revalidateRow($row, $request->input('row', []));
        Flash::success('Đã cập nhật và kiểm tra lại dòng ' . $row->row_number . '.');

        return redirect()->route('orders.smartImport.show', ['batch' => $batch->id, 'status' => $request->get('status')]);
    }

    public function confirm(SmartImportBatch $batch, SmartImportService $service)
    {
        $this->authorizeBatch($batch);
        $result = $service->importValidRows($batch);
        Flash::success('Đã import ' . $result['imported'] . ' dòng hợp lệ.');

        return redirect()->route('orders.smartImport.show', $batch->id);
    }

    public function destroy(SmartImportBatch $batch)
    {
        $this->authorizeBatch($batch);

        $filePath = $batch->file_path;
        $fileName = $batch->file_name;

        if ($filePath && File::exists($filePath)) {
            File::delete($filePath);
        }

        $batch->rows()->delete();
        $batch->delete();

        Flash::success('Đã xóa lô import "' . $fileName . '".');

        return redirect()->route('orders.smartImport.index');
    }

    private function authorizeBatch(SmartImportBatch $batch)
    {
        if ($batch->user_id && (int)$batch->user_id !== (int)auth()->id()) {
            abort(403);
        }
    }
}
