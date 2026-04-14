<?php
namespace App\Http\traits;

use Illuminate\Support\Facades\File;

trait FileHandleTrait
{
    public function fileUploadHandle(object|null $file, string $directory, string $name, string $prevFileName = null): string | null
    {
		if (isset($file)) {
			if ($file->isValid()) {

                $this->previousFileDelete($directory, $prevFileName);

				$fullFileName = preg_replace('/\s+/', '', $name) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path($directory), $fullFileName);

				return $fullFileName;
			}
		}
        return null;
    }


    public function previousFileDelete(string $directory, string|null $prevFileName) : void
    {
        if($prevFileName && File::exists(public_path($directory).'/'.$prevFileName))
            File::delete(public_path($directory).'/'.$prevFileName);
    }
}
