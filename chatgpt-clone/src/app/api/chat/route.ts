import OpenAI from "openai";

export const dynamic = "force-dynamic";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const messages = (body?.messages ?? []) as Array<{ role: "system" | "user" | "assistant"; content: string }>;
    const model = (body?.model as string) || process.env.OPENAI_MODEL || "gpt-4o-mini";
    const temperature = typeof body?.temperature === "number" ? body.temperature : 0.7;

    if (!process.env.OPENAI_API_KEY) {
      return new Response("Missing OPENAI_API_KEY", { status: 500 });
    }

    const client = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });

    const completionStream = await client.chat.completions.create({
      model,
      messages,
      temperature,
      stream: true,
    });

    const encoder = new TextEncoder();

    const stream = new ReadableStream<Uint8Array>({
      async start(controller) {
        try {
          for await (const part of completionStream) {
            const choice = part.choices?.[0];
            const delta = choice?.delta?.content ?? "";
            if (delta) {
              controller.enqueue(encoder.encode(delta));
            }
          }
        } catch (error) {
          const message = error instanceof Error ? error.message : String(error);
          controller.enqueue(encoder.encode(`\n[Stream error] ${message}`));
          controller.error(error);
        } finally {
          controller.close();
        }
      },
    });

    return new Response(stream, {
      headers: {
        "Content-Type": "text/plain; charset=utf-8",
        "Cache-Control": "no-cache",
      },
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    return new Response(`Bad request: ${message}`, { status: 400 });
  }
}