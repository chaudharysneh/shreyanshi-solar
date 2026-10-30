<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PdfBuilderForm;
use App\Models\PdfType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PdfbuilderApiController extends Controller
{
    private function extractRelativePath($imagePath)
    {
        if ($imagePath && filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($imagePath);
            if (isset($parsed['path'])) {
                $path = $parsed['path'];
                $imagePath = ltrim($path, '/');
                if (strpos($imagePath, 'public/') === 0) {
                    $imagePath = substr($imagePath, 7);
                }
            }
        }
        return $imagePath;
    }

    public function index(Request $request)
    {
        $query = PdfBuilderForm::select('id', 'template_name', 'created_at', 'pdf_file')
            ->orderBy('created_at', 'DESC');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('template_name', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%");
            });
        }

        $templates = $query->paginate(10);
        $templates->getCollection()->transform(function ($template) {
            $template->is_static_basic_template = Str::lower(trim((string) $template->template_name)) === 'basic template';

            return $template;
        });

        return response()->json([
            'success' => true,
            'message' => 'Templates retrieved successfully',
            'data' => $templates
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'template_name' => 'required|unique:pdf_builder_forms,template_name',
        ]);

        // BEFORE QUOTATION
        $before_titles = $request->input('before_title', []);
        $before_contents = $request->input('before_content', []);
        $before_ids = $request->input('before_id', []);
        $before_blocks = [];
        foreach ($before_titles as $i => $t) {
            $key = $before_ids[$i] ?? ('before_' . $i);
            $imgUrl = null;
            $absoluteImgPath = null;
            if ($request->hasFile("before_image.$key")) {
                $uploaded = $request->file("before_image.$key");
                $newName = time() . '_' . $uploaded->getClientOriginalName();
                $relPath = 'uploads/pdf_images/' . $newName;
                $uploaded->move(public_path('uploads/pdf_images/'), $newName);
                $absoluteImgPath = public_path($relPath);
                $imgUrl = asset($relPath);
            }
            $before_blocks[] = [
                'title'          => $t,
                'content'        => $before_contents[$i] ?? '',
                'image'          => $absoluteImgPath,
                'image_url'      => $imgUrl,
            ];
        }

        // AFTER QUOTATION
        $after_titles = $request->input('after_title', []);
        $after_contents = $request->input('after_content', []);
        $after_ids = $request->input('after_id', []);
        $after_blocks = [];
        foreach ($after_titles as $i => $t) {
            $key = $after_ids[$i] ?? ('after_' . $i);
            $imgUrl = null;
            $absoluteImgPath = null;
            if ($request->hasFile("after_image.$key")) {
                $uploaded = $request->file("after_image.$key");
                $newName = time() . '_' . $uploaded->getClientOriginalName();
                $relPath = 'uploads/pdf_images/' . $newName;
                $uploaded->move(public_path('uploads/pdf_images/'), $newName);
                $absoluteImgPath = public_path($relPath);
                $imgUrl = asset($relPath);
            }
            $after_blocks[] = [
                'title'          => $t,
                'content'        => $after_contents[$i] ?? '',
                'image'          => $absoluteImgPath,
                'image_url'      => $imgUrl,
            ];
        }

        $formData = [
            'before_blocks' => $before_blocks,
            'after_blocks'  => $after_blocks
        ];

        $firstImgPath = null;
        $firstImgAbsPath = null;
        if ($request->hasFile('first_img')) {
            $file = $request->file('first_img');
            $newName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/pdf_headers/'), $newName);
            $firstImgPath = 'uploads/pdf_headers/' . $newName;
            $firstImgAbsPath = public_path($firstImgPath);
        }

        $data = [
            'user_id'       => Auth::id() ?? 0,
            'form_title'    => $request->input('form_title', 'Custom PDF'),
            'form_data'     => $formData,
            'image_paths'   => array_merge(
                array_column($before_blocks, 'image_url'),
                array_column($after_blocks, 'image_url')
            ),
            'template_name' => $request->template_name,
            'first_img'     => $firstImgPath,
        ];

        $form = PdfBuilderForm::create($data);

        // PDF generation
        try {
            $pdfData = [
                'before_blocks'  => $before_blocks,
                'after_blocks'   => $after_blocks,
                'quotation_html' => $request->input('quotation_html', ''),
                'header_image'   => $firstImgAbsPath ?? public_path('uploads/pdf_headers/pink_tree_bg.png'),
                'footer_image'   => public_path('assets/pdfFooterNw.png')
            ];

            $pdf = Pdf::loadView('pdfbuilder.pdf_template', $pdfData);
            $pdfFileName = 'pdf_' . $form->id . '_' . time() . '.pdf';
            $pdfFilePath = 'uploads/pdfs/' . $pdfFileName;
            $pdf->save(public_path($pdfFilePath));

            $form->update(['pdf_file' => $pdfFilePath]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Template created successfully!',
            'template_id' => $form->id,
            'pdf_file' => asset($pdfFilePath)
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        $oldFormData = $template->form_data ?? [];
        $oldBeforeBlocks = $oldFormData['before_blocks'] ?? [];
        $oldAfterBlocks = $oldFormData['after_blocks'] ?? [];

        // BEFORE QUOTATION
        $before_titles = $request->input('before_title', []);
        $before_contents = $request->input('before_content', []);
        $before_ids = $request->input('before_id', []);
        $before_old_images = $request->input('before_image_old', []);

        $before_blocks = [];
        foreach ($before_titles as $i => $title) {
            $oldBlock = $oldBeforeBlocks[$i] ?? [];
            $key = $before_ids[$i] ?? ('before_' . $i);

            $imgUrl = null;
            $absoluteImgPath = null;

            if ($request->hasFile("before_image.$key")) {
                $uploaded = $request->file("before_image.$key");
                $newName = time() . '_' . $uploaded->getClientOriginalName();
                $relPath = 'uploads/pdf_images/' . $newName;
                $uploaded->move(public_path('uploads/pdf_images/'), $newName);
                $absoluteImgPath = public_path($relPath);
                $imgUrl = asset($relPath);
            } elseif (!empty($before_old_images[$key])) {
                // Preserve old image - reconstruct absolute path
                $absoluteImgPath = $oldBlock['image'] ?? null;
                $imgUrl = $oldBlock['image_url'] ?? null;
            } else {
                // Use old block data if available
                $absoluteImgPath = $oldBlock['image'] ?? null;
                $imgUrl = $oldBlock['image_url'] ?? null;
            }

            $before_blocks[] = [
                'title' => $title ?: ($oldBlock['title'] ?? ''),
                'content' => $before_contents[$i] ?: ($oldBlock['content'] ?? ''),
                'image' => $absoluteImgPath,
                'image_url' => $imgUrl,
            ];
        }

        // AFTER QUOTATION
        $after_titles = $request->input('after_title', []);
        $after_contents = $request->input('after_content', []);
        $after_ids = $request->input('after_id', []);
        $after_old_images = $request->input('after_image_old', []);

        $after_blocks = [];
        foreach ($after_titles as $i => $title) {
            $oldBlock = $oldAfterBlocks[$i] ?? [];
            $key = $after_ids[$i] ?? ('after_' . $i);

            $imgUrl = null;
            $absoluteImgPath = null;

            if ($request->hasFile("after_image.$key")) {
                $uploaded = $request->file("after_image.$key");
                $newName = time() . '_' . $uploaded->getClientOriginalName();
                $relPath = 'uploads/pdf_images/' . $newName;
                $uploaded->move(public_path('uploads/pdf_images/'), $newName);
                $absoluteImgPath = public_path($relPath);
                $imgUrl = asset($relPath);
            } elseif (!empty($after_old_images[$key])) {
                // Preserve old image - reconstruct absolute path
                $absoluteImgPath = $oldBlock['image'] ?? null;
                $imgUrl = $oldBlock['image_url'] ?? null;
            } else {
                // Use old block data if available
                $absoluteImgPath = $oldBlock['image'] ?? null;
                $imgUrl = $oldBlock['image_url'] ?? null;
            }

            $after_blocks[] = [
                'title' => $title ?: ($oldBlock['title'] ?? ''),
                'content' => $after_contents[$i] ?: ($oldBlock['content'] ?? ''),
                'image' => $absoluteImgPath,
                'image_url' => $imgUrl,
            ];
        }

        $firstImgPath = $template->first_img;
        $firstImgAbsPath = null;
        if ($request->hasFile('first_img')) {
            $file = $request->file('first_img');
            $newName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/pdf_headers/'), $newName);
            $firstImgPath = 'uploads/pdf_headers/' . $newName;
            $firstImgAbsPath = public_path($firstImgPath);
        } elseif ($firstImgPath) {
            $firstImgAbsPath = public_path($firstImgPath);
        }

        $formData = [
            'before_blocks' => $before_blocks,
            'after_blocks'  => $after_blocks
        ];

        $updateData = [
            'form_title'    => $request->input('form_title', $template->form_title),
            'template_name' => $request->input('template_name', $template->template_name),
            'form_data'     => $formData,
            'image_paths'   => array_merge(
                array_column($before_blocks, 'image_url'),
                array_column($after_blocks, 'image_url')
            ),
            'first_img'     => $firstImgPath,
        ];

        $template->update($updateData);

        // Re-generate PDF
        $pdfData = [
            'before_blocks'  => $before_blocks,
            'after_blocks'   => $after_blocks,
            'quotation_html' => $request->input('quotation_html', ''),
            'header_image'   => $firstImgAbsPath ?? public_path('uploads/pdf_headers/pink_tree_bg.png'),
            'footer_image'   => public_path('assets/pdfFooterNw.png')
        ];

        try {
            $pdf = Pdf::loadView('pdfbuilder.pdf_template', $pdfData);
            $pdfFileName = 'pdf_' . $id . '_' . time() . '.pdf';
            $pdfFilePath = 'uploads/pdfs/' . $pdfFileName;
            $pdf->save(public_path($pdfFilePath));
            $template->update(['pdf_file' => $pdfFilePath]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Template updated successfully',
            'pdf_file' => asset($pdfFilePath)
        ]);
    }

    public function editForm($id)
    {
        $url = route('pdfbuilder.api.edit-form-html', ['id' => $id]);

        return response()->json([
            'status' => true,
            'message' => 'Edit form URL generated successfully',
            'data' => [
                'url' => $url
            ]
        ]);
    }

    public function createFormHtml()
    {
        $types = PdfType::orderBy('name')->get();

        return view('pdfbuilder.form', [
            'types' => $types,
            'api_mode' => true
        ]);
    }

    public function createFormHtmlLink()
    {
        $id = request('id');
        $url = route('pdfbuilder.api.create-form-html', ['id' => $id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Form URL generated successfully',
            'url'     => $url
        ]);
    }

    public function editFormHtmlLink()
    {
        $id = request('edit_id');
        $user_id = request('id') ?? Auth::id();

        if (empty($id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Template ID is required for edit form'
            ], 400);
        }

        $url = route('pdfbuilder.api.edit-form-html', ['id' => $id, 'user_id' => $user_id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Edit form URL generated successfully',
            'url'     => $url
        ]);
    }

    public function editFormHtml($id)
    {
        $template = PdfBuilderForm::findOrFail($id);
        $user_id = request('user_id') ?? Auth::id();

        $form_data = $template->form_data ?? [];

        return view('pdfbuilder.form', [
            'template' => $template,
            'before_blocks' => $form_data['before_blocks'] ?? [],
            'after_blocks' => $form_data['after_blocks'] ?? [],
            'edit_mode' => true,
            'api_mode' => true,
            'user_id' => $user_id
        ]);
    }

    public function delete($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        if (!empty($template->pdf_file) && File::exists(public_path($template->pdf_file))) {
            File::delete(public_path($template->pdf_file));
        }

        $template->delete();

        return response()->json([
            'status' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    public function pdftemplateview($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        $formData = $template->form_data ?? [];
        $pdfData = [
            'before_blocks' => $formData['before_blocks'] ?? [],
            'after_blocks' => $formData['after_blocks'] ?? [],
            'quotation_html' => '',
            'header_image' => $template->first_img ? public_path($template->first_img) : public_path('uploads/pdf_headers/pink_tree_bg.png'),
            'footer_image' => public_path('assets/pdfFooterNw.png')
        ];

        try {
            $pdf = Pdf::loadView('pdfbuilder.pdf_template', $pdfData);
            $pdfFileName = 'pdf_view_' . $id . '_' . time() . '.pdf';
            $pdfFilePath = 'uploads/pdf_views/' . $pdfFileName;
            
            if (!File::isDirectory(public_path('uploads/pdf_views/'))) {
                File::makeDirectory(public_path('uploads/pdf_views/'), 0777, true);
            }
            
            $pdf->save(public_path($pdfFilePath));
            $viewUrl = asset($pdfFilePath);

            return response()->json([
                'status' => true,
                'message' => 'PDF template view generated successfully',
                'view_url' => $viewUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate PDF view: ' . $e->getMessage()
            ]);
        }
    }
}
