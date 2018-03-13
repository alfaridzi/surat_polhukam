<?php

namespace App\Http\Controllers\Log\RegistrasiNaskahMasuk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Model\Naskah\Naskah;
use App\Model\Penerima;
use App\Model\Pengaturan\JenisNaskah;
use App\Model\Pengaturan\Perkembangan;
use App\Model\Pengaturan\Urgensi;
use App\Model\Pengaturan\SifatNaskah;
use App\Model\Pengaturan\MediaArsip;
use App\Model\Pengaturan\Bahasa;
use App\Model\Pengaturan\SatuanUnit;
use App\berkas;
use App\klasifikasi;
use App\Model\Files;
use App\Model\User;
use Storage;
use Carbon\Carbon;

use App\Http\Requests\NaskahMasuk\UbahMetadataRequest;

use Auth;
use File;

class LogController extends Controller
{
    public function rgNaskahMasuk()
    {
    	$user = Auth::user();
    	$naskah = Naskah::where('tipe_registrasi', '1')->whereHas('user', function($q) use($user){
    		$q->where('id_jabatan', $user->id_jabatan);
    	})->with(['penerima' => function($q){
    		$q->where('sebagai', 'to')->groupBy('id_group');
    	}])->with('urgensi')->orderBy('id_naskah', 'asc')->get();

        // $naskah = Naskah::where('tipe_registrasi', '1')->with(['penerima' => function($q){
        //     $q->where('sebagai', 'to')->groupBy('id_group');
        // }])->with('urgensi')->orderBy('id_naskah', 'asc')->get();
    	$no = 1;
    	return view('log.registrasi_naskah_masuk.index', compact('naskah', 'no'));
    }

    public function detailRgNaskahMasuk($id)
    {
    	$getNaskah = Naskah::findOrFail($id);

        $user = Auth::user();
        $update = Penerima::where('id_naskah', $id)->where('kirim_user', $user->id_user)->get();
        if (!$update->isEmpty()) {
            foreach($update as $data)
            {
                $data->update(['status_naskah' => '1']);
            }
        }
        $metadataNaskah = Naskah::where('id_naskah', $id)->with('urgensi', 'jenisNaskah', 'tingkatPerkembangan', 'sifatNaskah', 'mediaArsip', 'bahasas', 'satuanUnit')->first();

        $naskah = Penerima::where('id_naskah', $id)->where(function($q) use($user, $id){
            $q->WhereHas('tujuan_kirim', function($query) use ($user){
                $query->where('id_jabatan', $user->id_jabatan);
            })->orWhereHas('user', function($query) use($user){
                $query->where('id_jabatan', $user->id_jabatan);
            });
        })->with('user')->with(['files' => function($q) use($id){
            $q->where('id_naskah', $id);
        }])->groupBy('id_group')->orderBy('id_penerima', 'DESC')->get();

        $naskah1 = Penerima::where('id_naskah', $id)->groupBy('id_group')->with('user')->with(['files' => function($q) use($id){
            $q->where('id_naskah', $id);
        }])->orderBy('id_penerima', 'desc')->get();

        $cekNaskah = Penerima::where('id_naskah', $id)->whereNotIn('sebagai', ['to_tl', 'bcc'])->whereHas('tujuan_kirim', function($q) use($user){
            $q->where('id_jabatan', $user->id_jabatan);
        })->orderBy('id_user', 'desc')->get();

        $dataUser = User::whereNotIn('id_user', [$user->id_user])->with('jabatan')->get()->toArray();
        $user = json_encode($dataUser);

        $no = 1;
        $no1 = 1;
        $cek = false;
        $cek1 = false;
    	return view('log.registrasi_naskah_masuk.detail', compact('user', 'metadataNaskah', 'cek', 'cek1', 'cekNaskah', 'cekTembusan', 'naskah', 'naskah1', 'no', 'no1', 'getNaskah'));
    }

    public function ubahMetaRgNaskahMasuk($id)
    {
    	$naskah = Naskah::where('id_naskah', $id)->with('sifatNaskah', 'bahasas', 'mediaArsip', 'satuanUnit', 'jenisNaskah', 'tingkatPerkembangan', 'urgensi')->first();

    	$dataNaskah = Naskah::first();
    	if (is_null($naskah)) {
    		$nomor_agenda = null;
    	}else{
    		$nomor_agenda = Naskah::orderBy('id_naskah', 'desc')->first()->nomor_agenda;
    	}

    	$jenisNaskah = JenisNaskah::all();
    	$perkembangan = Perkembangan::all();
    	$urgensi = Urgensi::all();
    	$sifatNaskah = SifatNaskah::all();
    	$mediaArsip = MediaArsip::all();
    	$bahasa = Bahasa::all();
    	$satuanUnit = SatuanUnit::all();
    	return view('log.registrasi_naskah_masuk.ubahMetadata', compact('naskah', 'jenisNaskah', 'perkembangan', 'urgensi', 'sifatNaskah', 'mediaArsip', 'bahasa', 'satuanUnit', 'nomor_agenda'));
    }

    public function updateMetaRgNaskahMasuk(UbahMetadataRequest $request, $id)
    {
    	$input = $request->all();
    	$naskah = Naskah::find($id);

    	$naskah->update($input);

    	return redirect('log/registrasi-naskah-masuk/detail/'.$id)->with('success', 'Berhasil update metadata');
    }

    public function downloadRgNaskahMasuk($id, $namaFile)
    {
        $getGroup = Files::where('nama_file', $namaFile)->first();
    	$file = Files::where('nama_file', $namaFile)->with(['penerima' => function($q) use($id, $getGroup){
            $q->where('id_naskah', $id)->where('id_group', $getGroup->id_group)->groupBy('id_group')->with('naskah');
        }])->first();

    	if (!File::exists('assets/FilesUploaded/'.$file->penerima->naskah->file_dir.'/'.$file->nama_file)) {
    		return redirect()->back()->withErrors('Tidak dapat menemukan file');
    	}

    	return response()->download('assets/FilesUploaded/'.$file->penerima->naskah->file_dir.'/'.$file->nama_file);
    }
    
}
