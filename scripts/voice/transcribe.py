#!/usr/bin/env python3
"""
Transcribe audio with faster-whisper (local STT).
Usage: python transcribe.py <audio_file> [--language es]
Stdout: transcribed text (single line).
"""
from __future__ import annotations

import argparse
import os
import sys


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("audio_path", help="Path to wav/m4a/mp3 audio")
    parser.add_argument("--language", default=os.environ.get("WHISPER_LANGUAGE", "es"))
    parser.add_argument(
        "--model",
        default=os.environ.get("FASTER_WHISPER_MODEL", "base"),
        help="faster-whisper model size or path",
    )
    args = parser.parse_args()

    if not os.path.isfile(args.audio_path):
        print(f"Audio no encontrado: {args.audio_path}", file=sys.stderr)
        return 1

    try:
        from faster_whisper import WhisperModel
    except ImportError:
        print(
            "Instala faster-whisper: pip install -r scripts/voice/requirements-voice.txt",
            file=sys.stderr,
        )
        return 2

    device = os.environ.get("WHISPER_DEVICE", "cpu")
    compute_type = os.environ.get("WHISPER_COMPUTE_TYPE", "int8")

    model = WhisperModel(args.model, device=device, compute_type=compute_type)
    segments, _info = model.transcribe(
        args.audio_path,
        language=args.language,
        vad_filter=True,
    )

    text = "".join(segment.text for segment in segments).strip()
    print(text)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
