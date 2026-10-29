<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    public function index(): View
    {
        return view('divisions.index', [
            'divisions' => Division::query()
                ->withCount(['users', 'laptops'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('divisions.form', ['division' => new Division]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:divisions,name'],
            'sections' => ['nullable', 'array', 'max:50'],
            'sections.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
        ]);
        $validated['sections'] = array_values(array_map('trim', $validated['sections'] ?? []));

        Division::query()->create($validated);

        return redirect()->route('divisions.index')->with('status', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division): View
    {
        return view('divisions.form', ['division' => $division]);
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')->ignore($division->id)],
            'sections' => ['nullable', 'array', 'max:50'],
            'sections.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
        ]);
        $validated['sections'] = array_values(array_map('trim', $validated['sections'] ?? []));

        $division->update($validated);

        return redirect()->route('divisions.index')->with('status', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        if ($division->users()->exists() || $division->laptops()->exists()) {
            return back()->withErrors([
                'division' => 'Divisi yang masih digunakan oleh karyawan atau laptop tidak dapat dihapus.',
            ]);
        }

        $division->delete();

        return redirect()->route('divisions.index')->with('status', 'Divisi berhasil dihapus.');
    }
}
