<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Partner;
use Flash;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $partners = Partner::get();
        return view('partners.index', ['partners' => $partners]);
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('partners.create', ['partner' => new Partner()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $formData = $request->all();
        $partner = Partner::create($formData);
        if(!$partner) {
            Flash::error('Lỗi khi tạo đơn vị vận chuyển');
        }else {
            Flash::success('Tạo đơn vị vận chuyển thành công');
        }
        return redirect()->route('partners.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $partner = Partner::where('id', $id)->first();
        if(!$partner){
            Flash::error('Đơn vị vận chuyển không tồn tại');
            return redirect()->route('partners.index');
        }
        return view('partners.edit', ['partner' => $partner]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $name = $request->name;
        $prefix_code = $request->prefix_code;
        Partner::where('id', $id)->update([
            'name' => $name,
            'prefix_code' => $prefix_code
        ]);
        Flash::success('Cận nhật đơn vị vận chuyển thành công');
        return redirect()->route('partners.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Partner::where('id', $id)->delete();
        Flash::success('Xóa đơn vị vận chuyển thành công');
        return redirect()->route('partners.index');
    }
}
