<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\UserLoginModel;
use App\Models\UserModel;

class FileController extends Controller
{

    public function upload(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'dokumen'   =>"required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $file=$login_data['id_user']."__".date("YmdHis")."__".preg_replace("/\s+/", "-", $request->file("dokumen")->getClientOriginalName());
        $file_name=$request->file("dokumen")->getClientOriginalName();
        $file_size=$request->file("dokumen")->getSize();

        //upload
        $request->file("dokumen")->move(storage_path(env("UPLOAD_PATH")), $file);

        return response()->json([
            'data'  =>[
                'file'      =>$file,
                'file_name' =>$file_name,
                'size'      =>$file_size/1000
            ]
        ]);
    }

    public function upload_avatar(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'image'   =>"required|file|max:10240|mimes:jpg,jpeg,png"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $file=$login_data['id_user']."__".date("YmdHis")."__".preg_replace("/\s+/", "-", $request->file("image")->getClientOriginalName());
        $file_name=$request->file("image")->getClientOriginalName();

        //upload
        Image::make($request->file("image")->path())->fit(150)->save(storage_path(env("UPLOAD_PATH"))."/".$file);
        $file_size=strlen(file_get_contents(storage_path(env("UPLOAD_PATH"))."/".$file));

        return response()->json([
            'data'  =>[
                'file'      =>$file,
                'file_name' =>$file_name,
                'size'      =>$file_size/1000
            ]
        ]);
    }

    public function show($file)
    {
        $upload_path=storage_path(env("UPLOAD_PATH"));

        if(file_exists($upload_path."/".$file)){
            $file_info=new \finfo(FILEINFO_MIME_TYPE);
            $file_show=file_get_contents($upload_path."/".$file);

            return response($file_show, 200)
                ->header('Content-Type', $file_info->buffer($file_show));
        }

        return response()->json([
            'status'=>"NOT_FOUND"
        ], 404);
    }
}