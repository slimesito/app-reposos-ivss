<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\CentroAsistencial;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuariosController extends Controller
{
    public function gestionUsuariosView()
    {
        $currentUser = auth()->user();

        $users = User::with(['servicio', 'centroAsistencial'])
            ->where('id', '!=', $currentUser->id)
            ->orderBy('apellidos')
            ->paginate(5);

        return view('admin.users.gestion_usuarios', ['users' => $users]);
    }

    public function buscadorUsuarios(Request $request)
    {
        $currentUser = auth()->user();
        $query = ucfirst(strtolower($request->input('usuariosQuery')));

        $users = User::where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('apellidos', 'LIKE', '%' . $query . '%')
                ->orWhere('cedula', 'LIKE', '%' . $query . '%');
        })->where('id', '!=', $currentUser->id)
        ->paginate(10);

        return view('admin.users.resultados_busqueda', compact('users'));
    }

    public function registroUsuariosView()
    {
        $servicios = Servicio::all();
        $centrosAsistenciales = CentroAsistencial::all();
        $cargos = Cargo::all();
        return view('admin.users.nuevo_usuario', compact('servicios', 'centrosAsistenciales', 'cargos'));
    }

    public function registerUsuarios(Request $request)
    {
        $request->validate([
            'nombres' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'cedula' => 'required|unique:users,cedula|max:10',
            'email' => 'required|unique:users,email|email',
            'password' => 'min:8|confirmed',
            'nro_mpps' => 'max:25|unique:users,nro_mpps',
            'cod_cargo' => 'required|max:5',
            'telefono' => 'required|max:20|unique:users,telefono',
            'telefono_oficina' => 'required|max:20',
            'id_servicio' => 'required|max:19',
            'id_centro_asistencial' => 'required|max:19',
            'sello' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'firma' => 'nullable|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('sello')) {
            $selloPath = $request->file('sello')->store('public/app/assets/sellos');
            // Guardar la ruta del sello en la base de datos
        }

        if ($request->hasFile('firma')) {
            $firmaPath = $request->file('firma')->store('public/app/assets/firmas');
            // Guardar la ruta de la firma en la base de datos
        }

        try {

            // Obtener ID usando secuencia Oracle
            $sequenceName = 'USERS_ID_SEQ'; // Asegúrate de tener esta secuencia
            $id = DB::selectOne("SELECT $sequenceName.NEXTVAL AS next_id FROM DUAL")->next_id;

            // Obtener usuario autenticado (el que está creando)
            $currentUser = Auth::user();

            User::create([
                'id' => $id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'cedula' => $request->cedula,
                'email' => $request->email,
                'password' => Hash::make('12345678'),
                'nro_mpps' => $request->nro_mpps,
                'cod_cargo' => $request->cod_cargo,
                'telefono' => $request->telefono,
                'telefono_oficina' => $request->telefono_oficina,
                'id_servicio' => $request->id_servicio,
                'id_centro_asistencial' => $request->id_centro_asistencial,
                'activo' => 0,
                'id_create' => auth()->user()->id,
                'fecha_create' => now(),
                'sello' => $selloPath ?? null,
                'firma' => $firmaPath ?? null,
            ]);

            return redirect('/inicio')->with('success', '¡Usuario registrado correctamente!');

        } catch (\Exception $e) {
            Log::error('Error al registrar el usuario: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el usuario. Inténtalo nuevamente.');
        }
    }

    public function editarUsuariosView($id)
    {
        $user = User::findOrFail($id);
        $servicios = Servicio::all();
        $centrosAsistenciales = CentroAsistencial::all();
        $cargos = Cargo::all();
        return view('admin.users.editar_usuarios', compact('user', 'servicios', 'centrosAsistenciales', 'cargos'));
    }

    public function updateUsuarios(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nombres' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'email' => 'required|unique:users,email,' . $user->id . '|email',
            'nro_mpps' => 'max:25',
            'cod_cargo' => 'required|max:5',
            'telefono' => 'required|max:20',
            'telefono_oficina' => 'required|max:20',
            'id_servicio' => 'required|max:19',
            'id_centro_asistencial' => 'required|max:19',
            'sello' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'firma' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'activo' => 'required|boolean',
        ]);

        $user->nombres = $request->input('nombres');
        $user->apellidos = $request->input('apellidos');
        $user->email = $request->input('email');
        $user->nro_mpps = $request->input('nro_mpps');
        $user->cod_cargo = $request->input('cod_cargo');
        $user->telefono = $request->input('telefono');
        $user->telefono_oficina = $request->input('telefono_oficina');
        $user->id_servicio = $request->input('id_servicio');
        $user->id_centro_asistencial = $request->input('id_centro_asistencial');
        $user->activo = $request->input('activo');
        $user->id_update = auth()->user()->id;
        $user->fecha_update = now();

        if ($request->hasFile('sello')) {
            $selloPath = $request->file('sello')->store('public/app/assets/sellos');
            $user->sello = $selloPath;
        }

        if ($request->hasFile('firma')) {
            $firmaPath = $request->file('firma')->store('public/app/assets/firmas');
            $user->firma = $firmaPath;
        }

        $user->save();

        return redirect('/gestion_usuarios')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroyUsuarios($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'Usuario eliminado correctamente');
    }

    public function usuariosPorAprobarView()
    {
        $usuarios = User::with(['servicio', 'centroAsistencial'])
                        ->where('activo', 0)
                        ->whereNull('fecha_update')
                        ->orderBy('fecha_create', 'desc')
                        ->paginate(10);

        return view('admin.users.aprobar_usuarios', ['usuarios' => $usuarios]);
    }

    public function buscadorUsuariosporAprobar(Request $request)
    {
        $currentUser = auth()->user();
        $query = ucfirst(strtolower($request->input('aprobarUsuariosQuery')));

        $users = User::where(function ($queryBuilder) use ($query) {
                $queryBuilder->where('apellidos', 'LIKE', '%' . $query . '%')
                    ->orWhere('cedula', 'LIKE', '%' . $query . '%');
            })
            ->where('id', '!=', $currentUser->id)
            ->where('activo', 0)
            ->whereNull('fecha_update')
            ->paginate(10);

        return view('admin.users.aprobar_resultados_busqueda', compact('users'));
    }

    public function aprobarUsuario($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->activo = 1;
        $usuario->id_update = auth()->user()->id;
        $usuario->fecha_update = now();
        $usuario->save();

        return redirect()->back()->with('success', 'Reposo aprobado correctamente.');
    }

    public function rechazarUsuario($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return redirect()->back()->with('success', 'Usuario rechazado');
    }

    // PERFIL USUARIOS ⬇⬇⬇⬇⬇⬇⬇

    public function showProfileSettings($id)
    {
        $user = User::findOrFail($id);

        return view('users.profile_settings', compact('user'));
    }

}
