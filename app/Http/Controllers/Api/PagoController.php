<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Curso;
use App\Models\Transaccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Charge;

class PagoController extends Controller
{
    // Procesar pago de un curso
    public function pagar(Request $request, $cursoId)
    {
        $request->validate([
            'metodo_pago' => 'required|in:paypal,tarjeta',
            'token' => 'required_if:metodo_pago,tarjeta', // Stripe token
            'paypal_order_id' => 'required_if:metodo_pago,paypal',
        ]);

        $curso = Curso::findOrFail($cursoId);
        $usuario = Auth::user();

        // Verificar si ya pagó
        if (Transaccion::where('user_id', $usuario->id)->where('curso_id', $cursoId)->where('estado', 'completada')->exists()) {
            return response()->json(['message' => 'Ya has pagado este curso.'], 409);
        }

        DB::beginTransaction();
        try {
            $referencia = null;
            $estado = 'pendiente';
            $monto = $curso->precio;

            if ($request->metodo_pago === 'tarjeta') {
                // Integración real con Stripe
                Stripe::setApiKey(env('STRIPE_SECRET'));
                $charge = \Stripe\Charge::create([
                    'amount' => intval($monto * 100), // Stripe usa centavos
                    'currency' => 'mxn',
                    'description' => 'Pago de curso: ' . $curso->titulo,
                    'source' => $request->token,
                    'metadata' => [
                        'user_id' => $usuario->id,
                        'curso_id' => $curso->id
                    ]
                ]);
                if ($charge->status !== 'succeeded') {
                    throw new \Exception('El pago con tarjeta no fue exitoso.');
                }
                $referencia = $charge->id;
                $estado = 'completada';
            } elseif ($request->metodo_pago === 'paypal') {
                // Aquí iría la integración real con PayPal (por ahora omitido)
                $referencia = $request->paypal_order_id;
                $estado = 'completada';
            }

            $transaccion = Transaccion::create([
                'user_id' => $usuario->id,
                'curso_id' => $curso->id,
                'monto' => $monto,
                'metodo_pago' => $request->metodo_pago,
                'estado' => $estado,
                'referencia' => $referencia,
                'fecha_pago' => now(),
            ]);

            // Dar acceso al curso (relación en curso_usuario)
            $curso->alumnos()->syncWithoutDetaching([$usuario->id => ['tipo_acceso' => 'pago']]);

            DB::commit();
            return response()->json(['message' => 'Pago exitoso', 'transaccion' => $transaccion], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en pago: ' . $e->getMessage());
            return response()->json(['message' => 'Error al procesar el pago: ' . $e->getMessage()], 500);
        }
    }

    // Obtener pagos/ingresos del profesor
    public function pagosProfesor()
    {
        $usuario = Auth::user();
        if (!$usuario->hasRole('profesor')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        // Buscar todos los cursos del profesor
        $cursos = $usuario->cursosInstructor()->pluck('id');
        // Buscar todas las transacciones completadas de esos cursos
        $pagos = \App\Models\Transaccion::whereIn('curso_id', $cursos)
            ->where('estado', 'completada')
            ->with(['usuario', 'curso'])
            ->orderBy('fecha_pago', 'desc')
            ->get();
        return response()->json(['pagos' => $pagos]);
    }
} 