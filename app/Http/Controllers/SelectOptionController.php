<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectOptionStoreRequest;
use App\Http\Requests\SelectOptionUpdateRequest;
use App\Http\Resources\SelectOption as SelectOptionResources;
use App\Http\Resources\SelectOptionSimple as SelectOptionSimpleResources;
use App\Models\SelectOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectOptionController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Option')):
            $keyword = $request->keyword;
            $perPage = $request->per_page;

            $options = SelectOption::when($keyword <> '', function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('value', 'like', "%{$keyword}%")
                    ->orWhere('text', 'like', "%{$keyword}%");
            })->orderBy('id', 'desc');

            $options = $perPage == 'all' ? $options->get() : $options->paginate($perPage);
            return SelectOptionResources::collection($options);
        else:
            return $this->unAuthorized();
        endif;
    }

    public function store(SelectOptionStoreRequest $request)
    {
        if ($this->user->can('Tambah Option')):
            $data = $request->all();
            return SelectOption::create($data) ? $this->storeTrue('opsi') : $this->storeFalse('opsi');
        else:
            return $this->unAuthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Option')):
            $option = SelectOption::find($id);
            if ($option) {
                return new SelectOptionResources($option);
            }

            return $this->dataNotFound('opsi');
        else:
            return $this->unAuthorized();
        endif;
    }

    public function update(SelectOptionUpdateRequest $request, $id)
    {
        if ($this->user->can('Update Option')):
            $option = SelectOption::find($id);
            if ($option) {
                $data = $request->except(['id']);
                return $option->update($data) ? $this->updateTrue('opsi') : $this->updateFalse('opsi');
            }

            return $this->dataNotFound('opsi');
        else:
            return $this->unAuthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Delete Option')):
            $option = SelectOption::find($id);
            if ($option) {
                return $option->delete() ? $this->destroyTrue('opsi') : $this->destroyFalse('opsi');
            }

            return $this->dataNotFound('opsi');
        else:
            return $this->unAuthorized();
        endif;
    }

    public function listByCode(Request $request)
    {
        $code = $request->code;
        if (is_array($code)) {
            $options = SelectOption::whereIn('code', $code)->get();
            return SelectOptionSimpleResources::collection($options);
        } else {
            $options = SelectOption::where('code', $code)->get();
            return SelectOptionSimpleResources::collection($options);
        }
    }
}
